<?php
/* Admin start page: all events (incl. drafts), upcoming first */
require __DIR__ . '/../../app/bootstrap.php';

$user = require_login();

$upcoming = [];
$past = [];
foreach (events_all() as $event) {
    if (event_is_upcoming($event)) {
        array_unshift($upcoming, $event); // soonest first
    } else {
        $past[] = $event;
    }
}

view('admin/head', ['title' => 'Events', 'user' => $user, 'section' => 'events']);
?>
<div class="admin-toolbar">
    <h1>Events</h1>
    <a class="btn btn--primary" href="/admin/event.php">+ Neues Event</a>
</div>

<?php foreach (['Anstehend' => $upcoming, 'Vergangen' => $past] as $heading => $events): ?>
<section class="admin-section">
    <h2><?= $heading ?> <span class="muted">(<?= count($events) ?>)</span></h2>
<?php if (!$events): ?>
    <p class="muted">Keine Events.</p>
<?php else: ?>
    <ul class="event-list">
<?php foreach ($events as $event): ?>
        <li class="event-row">
            <img class="event-row-image" src="<?= e(event_cover($event)) ?>" alt="" loading="lazy">
            <div class="event-row-info">
                <a class="event-row-title" href="/admin/event.php?id=<?= (int) $event['id'] ?>"><?= e($event['title']) ?></a>
                <span class="muted">
                    <?= e(format_date($event['starts_at'])) ?>
                    <?= $event['location'] !== '' ? ' · ' . e($event['location']) : '' ?>
                    <?= $event['category'] !== '' ? ' · ' . e($event['category']) : '' ?>
                </span>
            </div>
            <span class="status status--<?= $event['is_published'] ? 'published' : 'draft' ?>">
                <?= $event['is_published'] ? 'Veröffentlicht' : 'Entwurf' ?>
            </span>
            <div class="event-row-actions">
                <a class="btn btn--small" href="/admin/event.php?id=<?= (int) $event['id'] ?>">Bearbeiten</a>
                <a class="btn btn--small btn--ghost" href="<?= e(event_url($event)) ?>" target="_blank">Ansehen ↗</a>
            </div>
        </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
</section>
<?php endforeach; ?>

<?php view('admin/foot'); ?>
