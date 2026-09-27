<?php
/*
 * Event queries. Upcoming / past is decided by the date only:
 * an event counts as upcoming until the end of its day.
 * Public pages only see published events.
 */

declare(strict_types=1);

function event_is_upcoming(array $event): bool
{
    return substr($event['starts_at'], 0, 10) >= date('Y-m-d');
}

/** @return array<int, array> upcoming events, soonest first */
function events_upcoming(?int $limit = null): array
{
    $sql = 'SELECT * FROM events
            WHERE is_published = 1 AND substr(starts_at, 1, 10) >= :today
            ORDER BY starts_at ASC';
    if ($limit) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $statement = db()->prepare($sql);
    $statement->execute(['today' => date('Y-m-d')]);
    return $statement->fetchAll();
}

/** @return array<int, array> past events, newest first */
function events_past(?int $limit = null, ?int $excludeId = null): array
{
    $sql = 'SELECT * FROM events
            WHERE is_published = 1 AND substr(starts_at, 1, 10) < :today AND id != :exclude
            ORDER BY starts_at DESC';
    if ($limit) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $statement = db()->prepare($sql);
    $statement->execute(['today' => date('Y-m-d'), 'exclude' => $excludeId ?? 0]);
    return $statement->fetchAll();
}

function event_next(): ?array
{
    return events_upcoming(1)[0] ?? null;
}

function event_by_slug(string $slug): ?array
{
    $statement = db()->prepare('SELECT * FROM events WHERE slug = ? AND is_published = 1');
    $statement->execute([$slug]);
    return $statement->fetch() ?: null;
}

/** @return array<int, array> gallery images in their order */
function event_images(int $eventId): array
{
    $statement = db()->prepare('SELECT * FROM event_images WHERE event_id = ? ORDER BY position, id');
    $statement->execute([$eventId]);
    return $statement->fetchAll();
}

function event_url(array $event): string
{
    return '/events/' . rawurlencode($event['slug']);
}

/* Cover image, or a default one if the event has none yet */
const EVENT_DEFAULT_COVER = '/imgs/vibes/header-web.jpg';

function event_cover(array $event): string
{
    return $event['cover_image'] ?: EVENT_DEFAULT_COVER;
}
