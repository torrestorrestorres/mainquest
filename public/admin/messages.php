<?php
/*
 * Contact messages (all roles).
 *   /admin/messages.php               -> list (?filter=open|done|all)
 *   /admin/messages.php?id=5          -> one message. Opening a new message marks it "in progress".
 */
require __DIR__ . '/../../app/bootstrap.php';

$user = require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$message = $id ? contact_message_find($id) : null;
if ($id && !$message) {
    not_found();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        contact_message_delete($id);
        flash('success', "Anfrage von {$message['name']} wurde gelöscht.");
        redirect('/admin/messages.php');
    }
    if (array_key_exists($action, CONTACT_STATUS)) {
        contact_message_set_status($id, $action);
        flash('success', 'Status: ' . CONTACT_STATUS[$action]);
        redirect($action === 'done' ? '/admin/messages.php' : "/admin/messages.php?id={$id}");
    }
}

if ($message && $message['status'] === 'new') {
    contact_message_set_status($id, 'open');
    $message['status'] = 'open';
}

$filter = in_array($_GET['filter'] ?? '', ['open', 'done', 'all'], true) ? $_GET['filter'] : 'open';

view('admin/head', ['title' => $message ? "Anfrage von {$message['name']}" : 'Anfragen', 'user' => $user, 'section' => 'messages']);
?>

<?php if ($message): ?>
<?php
    $topic = CONTACT_TOPICS[$message['topic']] ?? $message['topic'];
    $replySubject = rawurlencode("Re: Deine Anfrage bei MainQuest ({$topic})");
?>
<div class="admin-toolbar">
    <h1><?= e($message['name']) ?></h1>
    <a class="btn btn--ghost" href="/admin/messages.php">← Alle Anfragen</a>
</div>

<section class="admin-card">
    <dl class="message-meta">
        <div><dt>Anliegen</dt><dd><?= e($topic) ?></dd></div>
        <div><dt>E-Mail</dt><dd><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></dd></div>
<?php if ($message['company'] !== ''): ?>
        <div><dt>Firma / Kanal</dt><dd><?= e($message['company']) ?></dd></div>
<?php endif; ?>
        <div><dt>Eingegangen</dt><dd><?= e(date('d.m.Y, H:i', strtotime($message['created_at']))) ?> Uhr</dd></div>
        <div><dt>Status</dt><dd><span class="status status--<?= e($message['status']) ?>"><?= e(CONTACT_STATUS[$message['status']]) ?></span></dd></div>
    </dl>
    <div class="message-text"><?= paragraphs($message['message']) ?></div>
    <div class="form-actions">
        <a class="btn btn--primary" href="mailto:<?= e($message['email']) ?>?subject=<?= $replySubject ?>">Per Mail antworten</a>
<?php foreach (CONTACT_STATUS as $status => $label): ?>
<?php if ($status !== $message['status'] && $status !== 'new'): ?>
        <form method="post">
            <?= csrf_field() ?>
            <button class="btn" type="submit" name="action" value="<?= $status ?>">Als „<?= $label ?>“ markieren</button>
        </form>
<?php endif; ?>
<?php endforeach; ?>
    </div>
</section>

<form class="danger-zone" method="post" data-confirm="Anfrage von <?= e($message['name']) ?> wirklich löschen?">
    <?= csrf_field() ?>
    <button class="btn btn--danger" type="submit" name="action" value="delete">Anfrage löschen</button>
</form>

<?php else: ?>
<div class="admin-toolbar">
    <h1>Anfragen</h1>
    <nav class="tabs" aria-label="Filter">
<?php foreach (['open' => 'Offen', 'done' => 'Erledigt', 'all' => 'Alle'] as $key => $label): ?>
        <a href="?filter=<?= $key ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= $label ?></a>
<?php endforeach; ?>
    </nav>
</div>

<?php $messages = contact_messages($filter); ?>
<?php if (!$messages): ?>
<p class="muted"><?= $filter === 'open' ? 'Keine offenen Anfragen – alles erledigt. 🎉' : 'Keine Anfragen.' ?></p>
<?php else: ?>
<ul class="event-list">
<?php foreach ($messages as $item): ?>
    <li class="event-row message-row<?= $item['status'] === 'new' ? ' message-row--new' : '' ?>">
        <div class="event-row-info">
            <a class="event-row-title" href="/admin/messages.php?id=<?= (int) $item['id'] ?>"><?= e($item['name']) ?></a>
            <span class="muted"><?= e(CONTACT_TOPICS[$item['topic']] ?? $item['topic']) ?> · <?= e(date('d.m.Y, H:i', strtotime($item['created_at']))) ?></span>
            <span class="message-preview"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $item['message']), 0, 140, ' …')) ?></span>
        </div>
        <span class="status status--<?= e($item['status']) ?>"><?= e(CONTACT_STATUS[$item['status']]) ?></span>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php endif; ?>

<?php view('admin/foot'); ?>
