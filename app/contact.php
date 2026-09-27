<?php
/*
 * Contact form: validation, spam protection, storing, notification, admin queries.
 *
 * Spam protection without captcha:
 *   - honeypot: a hidden field only bots fill in
 *   - time token: forms sent faster than CONTACT_MIN_SECONDS are from bots
 *   - rate limit: max CONTACT_LIMIT_PER_HOUR messages per IP (IP only stored as hash)
 */

declare(strict_types=1);

const CONTACT_TOPICS = [
    'event' => 'Event anfragen',
    'creator' => 'Ich bin Creator',
    'brand' => 'Brand / Kooperation',
    'other' => 'Sonstiges',
];
const CONTACT_STATUS = ['new' => 'Neu', 'open' => 'In Bearbeitung', 'done' => 'Erledigt'];
const CONTACT_MIN_SECONDS = 3;
const CONTACT_LIMIT_PER_HOUR = 5;
const CONTACT_MESSAGE_MAX = 5000;

function app_secret(): string
{
    return (string) (config('app_secret') ?: config('setup_token') ?: config('db_path'));
}

/* Hidden form field: when the form was shown, signed so it can't be faked */
function contact_time_token(): string
{
    $time = (string) time();
    return $time . '.' . hash_hmac('sha256', $time, app_secret());
}

function contact_time_token_valid(string $token): bool
{
    [$time, $signature] = array_pad(explode('.', $token, 2), 2, '');
    return hash_equals(hash_hmac('sha256', $time, app_secret()), $signature)
        && time() - (int) $time >= CONTACT_MIN_SECONDS
        && time() - (int) $time < 86400;
}

function contact_ip_hash(): string
{
    return hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', app_secret());
}

function contact_rate_limited(): bool
{
    $statement = db()->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip_hash = ? AND created_at > ?');
    $statement->execute([contact_ip_hash(), date('Y-m-d H:i:s', time() - 3600)]);
    return (int) $statement->fetchColumn() >= CONTACT_LIMIT_PER_HOUR;
}

/**
 * Checks the submitted form.
 * @return array{0: array, 1: array<string, string>, 2: bool} [values, errors per field, is spam]
 */
function contact_validate(array $post): array
{
    // single-line fields: line breaks and multiple spaces become one space
    $line = fn(string $key) => trim((string) preg_replace('/\s+/u', ' ', (string) ($post[$key] ?? '')));
    $values = [
        'name' => $line('name'),
        'email' => $line('email'),
        'company' => $line('company'),
        'topic' => (string) ($post['topic'] ?? ''),
        'message' => trim((string) ($post['message'] ?? '')),
    ];
    $isSpam = ($post['website'] ?? '') !== '' || !contact_time_token_valid((string) ($post['t'] ?? ''));

    $errors = [];
    if ($values['name'] === '') {
        $errors['name'] = 'Bitte gib deinen Namen an.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Bitte gib eine gültige E-Mail-Adresse an.';
    }
    if (!array_key_exists($values['topic'], CONTACT_TOPICS)) {
        $errors['topic'] = 'Bitte wähle ein Anliegen.';
    }
    if (mb_strlen($values['message']) < 10) {
        $errors['message'] = 'Erzähl uns bitte etwas mehr (mind. 10 Zeichen).';
    } elseif (mb_strlen($values['message']) > CONTACT_MESSAGE_MAX) {
        $errors['message'] = 'Die Nachricht ist zu lang (max. ' . CONTACT_MESSAGE_MAX . ' Zeichen).';
    }
    if (empty($post['privacy'])) {
        $errors['privacy'] = 'Bitte stimme der Datenschutzerklärung zu.';
    }

    return [$values, $errors, $isSpam];
}

function contact_save(array $values): int
{
    $now = date('Y-m-d H:i:s');
    db()->prepare('INSERT INTO contact_messages (name, email, company, topic, message, ip_hash, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$values['name'], $values['email'], $values['company'], $values['topic'], $values['message'], contact_ip_hash(), $now, $now]);
    return (int) db()->lastInsertId();
}

/* Notifies the team. "Reply" in the mail program answers the sender directly. */
function contact_notify(int $id, array $values): void
{
    $topic = CONTACT_TOPICS[$values['topic']];
    $text = "Neue Anfrage über die Website\n\n"
        . "Anliegen: {$topic}\n"
        . "Name:     {$values['name']}\n"
        . "E-Mail:   {$values['email']}\n"
        . ($values['company'] !== '' ? "Firma:    {$values['company']}\n" : '')
        . "\n{$values['message']}\n\n"
        . 'Im Admin öffnen: ' . absolute_url("/admin/messages.php?id={$id}");

    foreach (contact_recipients() as $recipient) {
        send_mail($recipient, "Neue Anfrage: {$topic} – {$values['name']}", $text, $values['email']);
    }
}

/* ---------- admin ---------- */

/** @return array<int, array> filter: open (new + in progress) | done | all */
function contact_messages(string $filter = 'open'): array
{
    $where = match ($filter) {
        'done' => "WHERE status = 'done'",
        'all' => '',
        default => "WHERE status != 'done'",
    };
    return db()->query("SELECT * FROM contact_messages {$where} ORDER BY created_at DESC")->fetchAll();
}

function contact_message_find(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

function contact_messages_count(string $status): int
{
    $statement = db()->prepare('SELECT COUNT(*) FROM contact_messages WHERE status = ?');
    $statement->execute([$status]);
    return (int) $statement->fetchColumn();
}

function contact_message_set_status(int $id, string $status): void
{
    db()->prepare('UPDATE contact_messages SET status = ?, updated_at = ? WHERE id = ?')
        ->execute([$status, date('Y-m-d H:i:s'), $id]);
}

function contact_message_delete(int $id): void
{
    db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
}
