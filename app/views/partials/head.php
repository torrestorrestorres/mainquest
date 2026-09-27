<?php
/**
 * Page start: <head> incl. share preview (Open Graph) + navigation.
 * @var string      $title        page title (without " · Main Quest")
 * @var string|null $description  text for Google / share previews
 * @var string|null $image        URL path of the share image
 */
$pageTitle = isset($title) && $title !== '' ? "{$title} · Main Quest" : 'Main Quest';
$description ??= 'MainQuest steht für kreative Konzepte, außergewöhnliche Experiences und Events für Creator und Communities.';
$image ??= '/imgs/vibes/header-web.jpg';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
<?php if (config('env') !== 'live'): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Main Quest">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:image" content="<?= e(absolute_url($image)) ?>">
    <meta property="og:url" content="<?= e(absolute_url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="stylesheet" href="<?= asset('design.css') ?>">
    <link rel="stylesheet" href="<?= asset('styles.css') ?>">
    <script src="<?= asset('components.js') ?>" defer></script>
    <script src="<?= asset('effects.js') ?>" defer></script>
</head>
<body>

<mq-nav></mq-nav>
