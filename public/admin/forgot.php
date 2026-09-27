<?php
/*
 * "Forgot password": sends a reset link by e-mail.
 * The answer is always the same, so nobody can find out which e-mails have an account.
 */
require __DIR__ . '/../../app/bootstrap.php';

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // counts like a failed login, so this can't be used to spam someone's inbox
    if (!login_is_blocked($email, $ip)) {
        db()->prepare('INSERT INTO login_attempts (email, ip) VALUES (?, ?)')->execute([$email, $ip]);
        $user = user_find_by_email($email);
        if ($user && $user['password_hash']) {
            $link = password_link(token_create((int) $user['id'], 'reset'));
            send_mail($user['email'], 'Passwort zurücksetzen – MainQuest Admin',
                "Hallo {$user['name']},\n\nüber diesen Link kannst du ein neues Passwort setzen (gültig für "
                . TOKEN_HOURS['reset'] . " Stunden):\n\n{$link}\n\nFalls du das nicht angefordert hast, ignoriere diese Mail einfach.");
        }
    }
    $sent = true;
}

view('admin/head', ['title' => 'Passwort vergessen']);
?>
<section class="admin-card admin-card--narrow">
    <h1>Passwort vergessen</h1>
<?php if ($sent): ?>
    <p>Falls es zu dieser Adresse einen Zugang gibt, ist jetzt eine E-Mail mit einem Link unterwegs.</p>
    <p class="muted">Keine Mail bekommen? Ein Admin kann dir unter „Benutzer“ auch direkt einen Link erstellen.</p>
<?php else: ?>
    <p class="muted">Gib deine E-Mail-Adresse ein, wir schicken dir einen Link zum Zurücksetzen.</p>
    <form class="form" method="post">
        <?= csrf_field() ?>
        <label class="field">E-Mail
            <input type="email" name="email" required autofocus autocomplete="username">
        </label>
        <button class="btn btn--primary" type="submit">Link anfordern</button>
    </form>
<?php endif; ?>
    <p class="muted"><a href="/admin/login.php">Zurück zum Login</a></p>
</section>
<?php view('admin/foot'); ?>
