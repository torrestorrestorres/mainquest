<?php
/*
 * Site settings, editable in the admin (Einstellungen) - no code change needed.
 */

declare(strict_types=1);

/* Social networks shown on the contact page: key => label */
const SOCIAL_NETWORKS = [
    'instagram' => 'Instagram',
    'tiktok' => 'TikTok',
    'youtube' => 'YouTube',
    'twitch' => 'Twitch',
    'linkedin' => 'LinkedIn',
];

function setting(string $key, string $default = ''): string
{
    static $settings = null;
    $settings ??= db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return ($settings[$key] ?? '') !== '' ? $settings[$key] : $default;
}

function settings_save(array $values): void
{
    $statement = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
        ON CONFLICT (key) DO UPDATE SET value = excluded.value');
    foreach ($values as $key => $value) {
        $statement->execute([$key, trim((string) $value)]);
    }
}

/** @return array<string, array{label: string, url: string}> only networks with a link */
function social_links(): array
{
    $links = [];
    foreach (SOCIAL_NETWORKS as $key => $label) {
        $url = setting("social_{$key}");
        if ($url !== '') {
            $links[$key] = ['label' => $label, 'url' => $url];
        }
    }
    return $links;
}

/** @return string[] e-mail addresses that get notified about new contact messages */
function contact_recipients(): array
{
    $list = setting('contact_recipients', (string) (config('mail')['reply_to'] ?? ''));
    return array_values(array_filter(
        array_map('trim', explode(',', $list)),
        fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
    ));
}
