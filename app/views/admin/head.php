<?php
/**
 * Admin page start: header with navigation + flash messages.
 * @var string     $title
 * @var array|null $user     logged-in user (null on login / setup / password pages)
 * @var string     $section  active menu item: events | messages | users | settings
 */
$user ??= null;
$section ??= '';
$navItem = fn(string $key, string $href, string $label, int $count = 0) => sprintf(
    '<a href="%s"%s>%s%s</a>', $href, $section === $key ? ' aria-current="page"' : '', e($label),
    $count ? " <span class=\"nav-count\" aria-label=\"{$count} neu\">{$count}</span>" : ''
);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · MainQuest Admin</title>
    <link rel="stylesheet" href="<?= asset('design.css') ?>">
    <link rel="stylesheet" href="<?= asset('admin/admin.css') ?>">
    <script src="<?= asset('admin/admin.js') ?>" defer></script>
</head>
<body class="admin">

<header class="admin-header">
    <a class="admin-logo" href="/admin/"><img src="/imgs/wortlogo.png" alt="MainQuest" width="166" height="52"> <span>Admin</span></a>
<?php if ($user): ?>
    <nav class="admin-nav" aria-label="Admin">
        <?= $navItem('events', '/admin/', 'Events') ?>
        <?= $navItem('messages', '/admin/messages.php', 'Anfragen', contact_messages_count('new')) ?>
<?php if ($user['role'] === 'admin'): ?>
        <?= $navItem('users', '/admin/users.php', 'Benutzer') ?>
        <?= $navItem('settings', '/admin/settings.php', 'Einstellungen') ?>
<?php endif; ?>
        <a href="/" target="_blank">Website ↗</a>
    </nav>
    <form class="admin-user" method="post" action="/admin/logout.php">
        <?= csrf_field() ?>
        <span><?= e($user['name'] ?: $user['email']) ?> · <?= e(ROLES[$user['role']]) ?></span>
        <button class="btn btn--ghost btn--small" type="submit">Abmelden</button>
    </form>
<?php endif; ?>
</header>

<main class="admin-main">
<?php foreach (take_flashes() as $flash): ?>
    <div class="flash flash--<?= e($flash['type']) ?>" role="status">
        <p><?= e($flash['message']) ?></p>
<?php if ($flash['link']): ?>
        <div class="copy-field">
            <input type="text" value="<?= e($flash['link']) ?>" readonly aria-label="Link">
            <button class="btn btn--small" type="button" data-copy>Kopieren</button>
        </div>
<?php endif; ?>
    </div>
<?php endforeach; ?>
