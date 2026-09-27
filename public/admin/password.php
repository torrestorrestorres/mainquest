<?php
/* Set a password via one-time link (invite or password reset), then log in directly */
require __DIR__ . '/../../app/bootstrap.php';

$token = (string) ($_GET['token'] ?? '');
$found = $token !== '' ? token_find($token) : null;
$errors = [];

if ($found && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($problem = password_problem($_POST['password'] ?? '', $_POST['password_confirm'] ?? '')) {
        $errors[] = $problem;
    } else {
        user_set_password((int) $found['id'], $_POST['password']);
        token_use((int) $found['token_id']);
        login_user((int) $found['id']);
        flash('success', $found['type'] === 'invite' ? 'Willkommen bei MainQuest! Dein Zugang ist aktiv.' : 'Dein neues Passwort ist gespeichert.');
        redirect('/admin/');
    }
}

view('admin/head', ['title' => 'Passwort setzen']);
?>
<section class="admin-card admin-card--narrow">
<?php if (!$found): ?>
    <h1>Link ungültig</h1>
    <p>Dieser Link ist abgelaufen oder wurde schon benutzt.</p>
    <p class="muted"><a href="/admin/forgot.php">Neuen Link anfordern</a> oder einen Admin um eine neue Einladung bitten.</p>
<?php else: ?>
    <h1><?= $found['type'] === 'invite' ? 'Willkommen' : 'Neues Passwort' ?><?= $found['name'] ? ', ' . e($found['name']) : '' ?></h1>
    <p class="muted">Lege dein Passwort für <strong><?= e($found['email']) ?></strong> fest.</p>
    <?php view('admin/errors', ['errors' => $errors]); ?>
    <form class="form" method="post">
        <?= csrf_field() ?>
        <input type="text" name="username" value="<?= e($found['email']) ?>" autocomplete="username" hidden>
        <label class="field">Passwort <small>(mind. <?= PASSWORD_MIN_LENGTH ?> Zeichen)</small>
            <input type="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autofocus autocomplete="new-password">
        </label>
        <label class="field">Passwort wiederholen
            <input type="password" name="password_confirm" required autocomplete="new-password">
        </label>
        <button class="btn btn--primary" type="submit">Passwort speichern</button>
    </form>
<?php endif; ?>
</section>
<?php view('admin/foot'); ?>
