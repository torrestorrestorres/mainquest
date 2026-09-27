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

/* $withDrafts: logged-in users can preview unpublished events */
function event_by_slug(string $slug, bool $withDrafts = false): ?array
{
    $statement = db()->prepare('SELECT * FROM events WHERE slug = ? AND (is_published = 1 OR ?)');
    $statement->execute([$slug, (int) $withDrafts]);
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

/* ---------- admin ---------- */

function event_find(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM events WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

/** @return array<int, array> all events incl. drafts, newest date first */
function events_all(): array
{
    return db()->query('SELECT * FROM events ORDER BY starts_at DESC')->fetchAll();
}

/** @return string[] categories already in use (suggestions in the admin form) */
function event_categories(): array
{
    return db()->query("SELECT DISTINCT category FROM events WHERE category != '' ORDER BY category")
        ->fetchAll(PDO::FETCH_COLUMN);
}

/* Slug that no other event uses yet: "summer-stage", "summer-stage-2", … */
function event_unique_slug(string $slug, ?int $ignoreId = null): string
{
    $statement = db()->prepare('SELECT 1 FROM events WHERE slug = ? AND id != ?');
    $candidate = $slug;
    for ($i = 2; ; $i++) {
        $statement->execute([$candidate, $ignoreId ?? 0]);
        if (!$statement->fetchColumn()) {
            return $candidate;
        }
        $candidate = "{$slug}-{$i}";
    }
}

const EVENT_FIELDS = ['slug', 'title', 'category', 'location', 'starts_at', 'teaser', 'body',
    'cover_alt', 'recap_url', 'ticket_url', 'is_published'];

/* Inserts or updates an event (only the fields above), returns its id */
function event_save(array $data, ?int $id = null): int
{
    $values = array_intersect_key($data, array_flip(EVENT_FIELDS));
    if ($id) {
        $set = implode(', ', array_map(fn($field) => "{$field} = :{$field}", array_keys($values)));
        db()->prepare("UPDATE events SET {$set}, updated_at = datetime('now') WHERE id = :id")
            ->execute($values + ['id' => $id]);
        return $id;
    }
    $fields = implode(', ', array_keys($values));
    $placeholders = implode(', ', array_map(fn($field) => ":{$field}", array_keys($values)));
    db()->prepare("INSERT INTO events ({$fields}) VALUES ({$placeholders})")->execute($values);
    return (int) db()->lastInsertId();
}

function event_set_cover(int $id, ?string $path): void
{
    db()->prepare("UPDATE events SET cover_image = ?, updated_at = datetime('now') WHERE id = ?")->execute([$path, $id]);
}

function event_delete(int $id): void
{
    db()->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
}

function event_image_add(int $eventId, string $path, string $alt = ''): void
{
    $position = (int) db()->query('SELECT COALESCE(MAX(position), -1) + 1 FROM event_images WHERE event_id = ' . $eventId)->fetchColumn();
    db()->prepare('INSERT INTO event_images (event_id, path, alt, position) VALUES (?, ?, ?, ?)')
        ->execute([$eventId, $path, $alt, $position]);
}

function event_image_update(int $eventId, int $imageId, string $alt, int $position): void
{
    db()->prepare('UPDATE event_images SET alt = ?, position = ? WHERE id = ? AND event_id = ?')
        ->execute([$alt, $position, $imageId, $eventId]);
}

/* Removes the image from the event and returns its path (to delete the file) */
function event_image_remove(int $eventId, int $imageId): ?string
{
    $statement = db()->prepare('SELECT path FROM event_images WHERE id = ? AND event_id = ?');
    $statement->execute([$imageId, $eventId]);
    $path = $statement->fetchColumn() ?: null;
    db()->prepare('DELETE FROM event_images WHERE id = ? AND event_id = ?')->execute([$imageId, $eventId]);
    return $path;
}

/* Upload folder of an event (by id, so renaming the event doesn't break paths) */
function event_upload_folder(int $id): string
{
    return "events/{$id}";
}

/* Cover image, or a default one if the event has none yet */
const EVENT_DEFAULT_COVER = '/imgs/vibes/header-web.jpg';

function event_cover(array $event): string
{
    return $event['cover_image'] ?: EVENT_DEFAULT_COVER;
}
