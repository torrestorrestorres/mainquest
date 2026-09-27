<?php
/* /events/<slug>: detail page. Upcoming -> info + tickets, past -> recap + aftermovie */
require __DIR__ . '/../app/bootstrap.php';

// logged-in users (admin) also see drafts, as a preview
$event = event_by_slug((string) ($_GET['slug'] ?? ''), current_user() !== null) ?? not_found();
$upcoming = event_is_upcoming($event);
$images = event_images((int) $event['id']);
$moreEvents = events_past(3, (int) $event['id']);
$cover = css_url(event_cover($event));

$meta = array_filter([format_date($event['starts_at']), $event['location'], $event['category']]);

view('partials/head', [
    'title' => $event['title'],
    'description' => $event['teaser'],
    'image' => event_cover($event),
]);
?>

<section id="hero" class="hero--compact"<?= $cover ? " style=\"--hero-image: url('{$cover}')\"" : '' ?>>
    <div class="hero-content">
        <mq-label variant="light"><?= $event['is_published'] ? ($upcoming ? 'Next Quest' : 'Recap') : 'Entwurf · nur für eingeloggte Benutzer sichtbar' ?></mq-label>
        <h1 class="heading-xl"><?= e($event['title']) ?></h1>
    </div>
    <div class="hero-footer">
        <p class="hero-text event-hero-meta"><?= e(implode(' · ', $meta)) ?></p>
<?php if ($upcoming && $event['ticket_url']): ?>
        <a class="button" href="<?= e($event['ticket_url']) ?>" target="_blank" rel="noopener">
            <span class="button-label">Tickets sichern</span>
            <span class="button-icon" aria-hidden="true">→</span>
        </a>
<?php elseif (!$upcoming && $event['recap_url']): ?>
        <a class="button" href="<?= e($event['recap_url']) ?>" target="_blank" rel="noopener">
            <span class="button-label">Aftermovie ansehen</span>
            <span class="button-icon" aria-hidden="true">→</span>
        </a>
<?php endif; ?>
    </div>
</section>

<section id="event-details" class="event-details">
    <mq-label><?= $upcoming ? 'Das erwartet dich' : 'So war die Quest' ?></mq-label>
    <div class="event-body">
        <?= paragraphs($event['body'] !== '' ? $event['body'] : $event['teaser']) ?>
    </div>
</section>

<?php if ($images): ?>
<section id="event-gallery" class="event-gallery">
    <mq-carousel class="gallery" start="center" role="region" aria-label="Bilder von <?= e($event['title']) ?>">
<?php foreach ($images as $image): ?>
        <img src="<?= e($image['path']) ?>" alt="<?= e($image['alt']) ?>" loading="lazy">
<?php endforeach; ?>
    </mq-carousel>
</section>
<?php endif; ?>

<?php if ($moreEvents): ?>
<section id="more-events" class="events-section">
    <div class="events-head">
        <div class="events-title">
            <mq-label>Weitere Quests</mq-label>
            <h2 class="heading-l">Das haben wir<br> auch <span class="highlighted">erlebt.</span></h2>
        </div>
        <a class="link-arrow dashed-line" href="/events">
            <span aria-hidden="true">→</span> Alle Events
        </a>
    </div>
    <div class="events-grid">
<?php foreach ($moreEvents as $moreEvent): ?>
        <?php view('partials/event-card', ['event' => $moreEvent]); ?>
<?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php view('partials/cta'); ?>

<?php view('partials/foot'); ?>
