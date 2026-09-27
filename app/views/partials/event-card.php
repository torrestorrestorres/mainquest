<?php
/**
 * One event as <mq-event-card> (rendered by components.js).
 * @var array $event row from the events table
 */
$upcoming = event_is_upcoming($event);
?>
<mq-event-card
    img="<?= e(event_cover($event)) ?>" alt="<?= e($event['cover_alt']) ?>"
    date="<?= e(substr($event['starts_at'], 0, 10)) ?>" location="<?= e($event['location']) ?>"
    category="<?= e($event['category']) ?>" heading="<?= e($event['title']) ?>"
    link="<?= e(event_url($event)) ?>" link-label="<?= $upcoming ? 'Mehr erfahren' : 'Zum Recap' ?>">
    <?= e($event['teaser']) ?>
</mq-event-card>
