<?php
/*
 * Fills an empty database with demo events (placeholders until the admin exists).
 * Run:  php app/cli/seed.php
 */

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$pdo = db();
if ((int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn() > 0) {
    exit("Database already contains events - nothing to do.\n");
}

$lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
$loremBody = "{$lorem} Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\n"
    . "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. "
    . "Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

$events = [
    ['Halloween Creator Night', 'Creator-Event', 'Köln', '2026-10-31 19:00', '/imgs/vibes/ferriswheel-web.jpg', 'Beleuchtetes Riesenrad bei Nacht'],
    ['Halloween Creator Night 2025', 'Creator-Event', 'Köln', '2025-10-31 19:00', '/imgs/vibes/header-web.jpg', 'Kostümierte Gäste auf einer Halloween-Party'],
    ['Summer Stage', 'Brand Activation', 'Berlin', '2025-08-16 18:00', '/imgs/vibes/party-web.jpg', 'Menschenmenge vor einer beleuchteten Bühne'],
    ['Late Night Session', 'Creator-Event', 'Hamburg', '2025-06-07 21:00', '/imgs/vibes/dj-web.jpg', 'DJ-Mischpult in blauem Licht'],
    ['Imagine Everything Premiere', 'Brand Activation', 'München', '2025-04-12 20:00', '/imgs/vibes/nightclub-web.jpg', 'Club mit Leinwand „Imagine Every Thing“'],
    ['Community Rave', 'Community', 'Köln', '2025-02-22 22:00', '/imgs/vibes/accordionParty.png', 'Feiernde Menschen auf einem Event'],
    ['Kirmes Quest', 'Community', 'Düsseldorf', '2024-09-14 17:00', '/imgs/vibes/ferriswheel-web.jpg', 'Beleuchtetes Riesenrad bei Nacht'],
];

$insertEvent = $pdo->prepare('INSERT INTO events
    (slug, title, category, location, starts_at, teaser, body, cover_image, cover_alt, recap_url, ticket_url, is_published)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
$insertImage = $pdo->prepare('INSERT INTO event_images (event_id, path, alt, position) VALUES (?, ?, ?, ?)');

$gallery = [
    ['/imgs/vibes/party-web.jpg', 'Menschenmenge vor einer beleuchteten Bühne'],
    ['/imgs/vibes/dj-web.jpg', 'DJ-Mischpult in blauem Licht'],
    ['/imgs/vibes/nightclub-web.jpg', 'Club mit Leinwand „Imagine Every Thing“'],
    ['/imgs/vibes/header-web.jpg', 'Kostümierte Gäste auf einer Halloween-Party'],
];

$pdo->beginTransaction();
foreach ($events as [$title, $category, $location, $startsAt, $cover, $alt]) {
    $upcoming = substr($startsAt, 0, 10) >= date('Y-m-d');
    $insertEvent->execute([
        slugify($title), $title, $category, $location, $startsAt, $lorem, $loremBody, $cover, $alt,
        $upcoming ? null : 'https://www.youtube.com/',
        $upcoming ? 'https://example.com/tickets' : null,
    ]);

    // past events get a small demo gallery
    if (!$upcoming) {
        $eventId = (int) $pdo->lastInsertId();
        foreach ($gallery as $position => [$path, $imageAlt]) {
            $insertImage->execute([$eventId, $path, $imageAlt, $position]);
        }
    }
}
$pdo->commit();

echo count($events) . " demo events created.\n";
