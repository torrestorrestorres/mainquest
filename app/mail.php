<?php
/*
 * Sending e-mails via PHP mail() (works on almost every web host).
 * With mail disabled in config.php (local), mails are written to data/mail.log instead.
 */

declare(strict_types=1);

/* $replyTo: e.g. the sender of a contact message, so "reply" goes straight to them */
function send_mail(string $to, string $subject, string $text, ?string $replyTo = null): bool
{
    $mail = config('mail') ?? [];
    $from = $mail['from'] ?? 'noreply@localhost';
    $fromName = $mail['from_name'] ?? 'MainQuest';
    // line breaks in the subject could inject extra mail headers
    $subject = trim((string) preg_replace('/[\r\n]+/', ' ', $subject));
    if (!$replyTo || !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $replyTo = $mail['reply_to'] ?? $from;
    }

    if (empty($mail['enabled'])) {
        $log = dirname((string) config('db_path')) . '/mail.log';
        file_put_contents($log, sprintf("[%s] To: %s\nReply-To: %s\nSubject: %s\n\n%s\n\n%s\n", date('Y-m-d H:i:s'), $to, $replyTo, $subject, $text, str_repeat('-', 60)), FILE_APPEND);
        return false;
    }

    $headers = [
        'From' => mb_encode_mimeheader($fromName) . " <{$from}>",
        'Reply-To' => $replyTo,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];

    return mail($to, mb_encode_mimeheader($subject), $text, $headers, '-f' . $from);
}
