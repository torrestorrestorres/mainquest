<?php
/*
 * Sending e-mails via PHP mail() (works on almost every web host).
 * With mail disabled in config.php (local), mails are written to data/mail.log instead.
 */

declare(strict_types=1);

function send_mail(string $to, string $subject, string $text): bool
{
    $mail = config('mail') ?? [];
    $from = $mail['from'] ?? 'noreply@localhost';
    $fromName = $mail['from_name'] ?? 'MainQuest';

    if (empty($mail['enabled'])) {
        $log = dirname((string) config('db_path')) . '/mail.log';
        file_put_contents($log, sprintf("[%s] To: %s\nSubject: %s\n\n%s\n\n%s\n", date('Y-m-d H:i:s'), $to, $subject, $text, str_repeat('-', 60)), FILE_APPEND);
        return false;
    }

    $headers = [
        'From' => mb_encode_mimeheader($fromName) . " <{$from}>",
        'Reply-To' => $mail['reply_to'] ?? $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];

    return mail($to, mb_encode_mimeheader($subject), $text, $headers, '-f' . $from);
}
