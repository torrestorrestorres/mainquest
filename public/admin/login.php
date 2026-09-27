<?php
/* Admin login */
require __DIR__ . '/../../app/bootstrap.php';

session_begin();
$next = safe_admin_path($_GET['next'] ?? $_POST['next'] ?? null);
if (current_user()) {
    redirect($next);
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $result = attempt_login($email, (string) ($_POST['password'] ?? ''));

    if (is_array($result)) {
        redirect($next);
    }
    $errors[] = $result;
}

view('admin/head', ['title' => 'Anmelden']);
?>
<section class="admin-card admin-card--narrow">
    <h1>Anmelden</h1>
    <?php view('admin/errors', ['errors' => $errors]); ?>
    <form class="form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label class="field">E-Mail
            <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">
        </label>
        <label class="field">Passwort
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn--primary" type="submit">Anmelden</button>
    </form>
    <p class="muted"><a href="/admin/forgot.php">Passwort vergessen?</a></p>
</section>
<?php view('admin/foot'); ?>
