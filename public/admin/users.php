<?php
/*
 * User management (admins only): invite, change role, password link, delete.
 * There is always at least one admin, and nobody can delete themselves.
 */
require __DIR__ . '/../../app/bootstrap.php';

$user = require_admin();
$errors = [];
$invite = ['email' => '', 'name' => '', 'role' => 'editor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $target = isset($_POST['user_id']) ? user_find((int) $_POST['user_id']) : null;
    $isLastAdmin = fn(array $someone) => $someone['role'] === 'admin' && users_count('admin') <= 1;

    if ($action === 'invite') {
        $invite = [
            'email' => trim($_POST['email'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'role' => array_key_exists($_POST['role'] ?? '', ROLES) ? $_POST['role'] : 'editor',
        ];
        if (!filter_var($invite['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte gib eine gültige E-Mail-Adresse ein.';
        } elseif (user_find_by_email($invite['email'])) {
            $errors[] = 'Zu dieser E-Mail-Adresse gibt es schon einen Zugang.';
        }

        if (!$errors) {
            $newId = user_create($invite['email'], $invite['name'], $invite['role']);
            $link = password_link(token_create($newId, 'invite'));
            $mailed = send_mail($invite['email'], 'Einladung zum MainQuest Admin',
                "Hallo {$invite['name']},\n\n" . ($user['name'] ?: $user['email']) . " hat dich zum MainQuest Admin eingeladen.\n"
                . "Über diesen Link legst du dein Passwort fest (gültig für " . TOKEN_HOURS['invite'] . " Stunden):\n\n{$link}");
            flash('success', ($mailed ? 'Einladung per E-Mail verschickt. ' : '')
                . 'Du kannst den Link auch selbst weitergeben (gültig für ' . TOKEN_HOURS['invite'] . ' Stunden):', $link);
            redirect('/admin/users.php');
        }
    } elseif ($target && $action === 'role') {
        $role = $_POST['role'] ?? '';
        if (!array_key_exists($role, ROLES)) {
            $errors[] = 'Unbekannte Rolle.';
        } elseif ($role !== 'admin' && $isLastAdmin($target)) {
            $errors[] = 'Es muss mindestens einen Admin geben.';
        } else {
            user_set_role((int) $target['id'], $role);
            flash('success', "Rolle von {$target['email']} ist jetzt „" . ROLES[$role] . '“.');
            redirect('/admin/users.php');
        }
    } elseif ($target && $action === 'link') {
        // new invite (not active yet) or password reset link (active users)
        $type = $target['password_hash'] ? 'reset' : 'invite';
        $link = password_link(token_create((int) $target['id'], $type));
        flash('success', "Link für {$target['email']} (gültig für " . TOKEN_HOURS[$type] . ' Stunden):', $link);
        redirect('/admin/users.php');
    } elseif ($target && $action === 'delete') {
        if ((int) $target['id'] === (int) $user['id']) {
            $errors[] = 'Du kannst dich nicht selbst löschen.';
        } elseif ($isLastAdmin($target)) {
            $errors[] = 'Es muss mindestens einen Admin geben.';
        } else {
            user_delete((int) $target['id']);
            flash('success', "{$target['email']} wurde gelöscht.");
            redirect('/admin/users.php');
        }
    }
}

view('admin/head', ['title' => 'Benutzer', 'user' => $user, 'section' => 'users']);
?>
<div class="admin-toolbar">
    <h1>Benutzer</h1>
</div>

<?php view('admin/errors', ['errors' => $errors]); ?>

<section class="admin-card">
    <h2>Person einladen</h2>
    <form class="form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="invite">
        <div class="field-row">
            <label class="field">Name
                <input type="text" name="name" value="<?= e($invite['name']) ?>" maxlength="80">
            </label>
            <label class="field">E-Mail
                <input type="email" name="email" value="<?= e($invite['email']) ?>" required>
            </label>
            <label class="field field--small">Rolle
                <select name="role">
<?php foreach (ROLES as $role => $label): ?>
                    <option value="<?= $role ?>" <?= $invite['role'] === $role ? 'selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
                </select>
            </label>
        </div>
        <p class="muted small"><strong>Editor:</strong> Events verwalten. <strong>Admin:</strong> zusätzlich Benutzer verwalten.</p>
        <button class="btn btn--primary" type="submit">Einladen</button>
    </form>
</section>

<section class="admin-section">
    <h2>Alle Zugänge</h2>
    <ul class="user-list">
<?php foreach (users_all() as $someone): ?>
<?php
    $isMe = (int) $someone['id'] === (int) $user['id'];
    $status = $someone['password_hash']
        ? ($someone['last_login_at'] ? 'Zuletzt aktiv ' . format_date($someone['last_login_at']) : 'Aktiv')
        : (user_has_open_invite((int) $someone['id']) ? 'Einladung offen' : 'Einladung abgelaufen');
?>
        <li class="user-row">
            <div class="user-row-info">
                <strong><?= e($someone['name'] ?: $someone['email']) ?><?= $isMe ? ' (du)' : '' ?></strong>
                <span class="muted"><?= e($someone['email']) ?> · <?= e($status) ?></span>
            </div>
            <form class="inline-form" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="role">
                <input type="hidden" name="user_id" value="<?= (int) $someone['id'] ?>">
                <select name="role" aria-label="Rolle von <?= e($someone['email']) ?>" data-autosubmit>
<?php foreach (ROLES as $role => $label): ?>
                    <option value="<?= $role ?>" <?= $someone['role'] === $role ? 'selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
                </select>
                <noscript><button class="btn btn--small" type="submit">Ändern</button></noscript>
            </form>
            <form class="inline-form" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="link">
                <input type="hidden" name="user_id" value="<?= (int) $someone['id'] ?>">
                <button class="btn btn--small btn--ghost" type="submit"><?= $someone['password_hash'] ? 'Passwort-Link' : 'Neue Einladung' ?></button>
            </form>
<?php if (!$isMe): ?>
            <form class="inline-form" method="post" data-confirm="<?= e($someone['email']) ?> wirklich löschen?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= (int) $someone['id'] ?>">
                <button class="btn btn--small btn--danger" type="submit">Löschen</button>
            </form>
<?php endif; ?>
        </li>
<?php endforeach; ?>
    </ul>
</section>

<?php view('admin/foot'); ?>
