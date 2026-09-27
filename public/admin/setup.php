<?php
/*
 * Creates the very first admin. Only works while no user exists
 * and only with the secret setup_token from config.php:  /admin/setup.php?token=…
 */
require __DIR__ . '/../../app/bootstrap.php';

$token = (string) ($_GET['token'] ?? '');
$setupToken = (string) config('setup_token');
if (users_count() > 0 || $setupToken === '' || !hash_equals($setupToken, $token)) {
    not_found();
}

$errors = [];
$input = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $input = ['name' => trim($_POST['name'] ?? ''), 'email' => trim($_POST['email'] ?? '')];

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte gib eine gültige E-Mail-Adresse ein.';
    }
    if ($problem = password_problem($_POST['password'] ?? '', $_POST['password_confirm'] ?? '')) {
        $errors[] = $problem;
    }

    if (!$errors) {
        $id = user_create($input['email'], $input['name'], 'admin', $_POST['password']);
        login_user($id);
        flash('success', 'Willkommen! Dein Admin-Zugang ist eingerichtet.');
        redirect('/admin/');
    }
}

view('admin/head', ['title' => 'Einrichtung']);
?>
<section class="admin-card admin-card--narrow">
    <h1>Admin einrichten</h1>
    <p class="muted">Lege den ersten Zugang an. Weitere Personen lädst du danach unter „Benutzer“ ein.</p>
    <?php view('admin/errors', ['errors' => $errors]); ?>
    <form class="form" method="post">
        <?= csrf_field() ?>
        <label class="field">Name
            <input type="text" name="name" value="<?= e($input['name']) ?>" autocomplete="name">
        </label>
        <label class="field">E-Mail
            <input type="email" name="email" value="<?= e($input['email']) ?>" required autocomplete="email">
        </label>
        <label class="field">Passwort <small>(mind. <?= PASSWORD_MIN_LENGTH ?> Zeichen)</small>
            <input type="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
        </label>
        <label class="field">Passwort wiederholen
            <input type="password" name="password_confirm" required autocomplete="new-password">
        </label>
        <button class="btn btn--primary" type="submit">Admin anlegen</button>
    </form>
</section>
<?php view('admin/foot'); ?>
