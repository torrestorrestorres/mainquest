<?php
/**
 * Highlight of the next upcoming event (dark split section).
 * Renders nothing when no event is planned.
 * @var array|null $event
 */
if (!$event) {
    return;
}
?>
<section id="next-quest" class="split-section">
    <div class="split-content">
        <mq-label variant="light">Next Quest</mq-label>
        <p class="event-meta">
            <time datetime="<?= e(substr($event['starts_at'], 0, 10)) ?>"><?= e(format_date($event['starts_at'])) ?></time>
            <?= $event['location'] !== '' ? '· ' . e($event['location']) : '' ?>
        </p>
        <h2 class="heading-l"><?= e($event['title']) ?></h2>
        <p class="split-text"><?= e($event['teaser']) ?></p>
        <a class="button" href="<?= e(event_url($event)) ?>">
            <span class="button-label">Mehr erfahren</span>
            <span class="button-icon" aria-hidden="true">→</span>
        </a>
    </div>
    <img class="split-image" src="<?= e(event_cover($event)) ?>" alt="<?= e($event['cover_alt']) ?>" loading="lazy">
</section>
