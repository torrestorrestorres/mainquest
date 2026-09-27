<?php
/* /events: next quest, further upcoming events, all past events */
require __DIR__ . '/../app/bootstrap.php';

$upcomingEvents = events_upcoming();
$next = array_shift($upcomingEvents);
$pastEvents = events_past();

view('partials/head', [
    'title' => 'Unsere Events',
    'description' => 'Creator-Events, Community Experiences und Brand Activations von MainQuest.',
    'image' => $next ? event_cover($next) : null,
]);
?>

<section id="hero" class="hero--compact" style="--hero-image: url('/imgs/vibes/party-web.jpg')">
    <div class="hero-content">
        <mq-label variant="light">Unsere Events</mq-label>
        <h1 class="heading-xl">Quests, die wir<br> schon erlebt haben.</h1>
    </div>
    <div class="hero-footer">
        <p class="hero-text">
            Von exklusiven Creator-Events über Community Experiences bis hin zu
            außergewöhnlichen Brand Activations: Hier siehst du, was wir schon auf die Beine gestellt haben.
        </p>
<?php if ($next): ?>
        <a class="button" href="#next-quest">
            <span class="button-label">Zur Next Quest</span>
            <span class="button-icon" aria-hidden="true">→</span>
        </a>
<?php endif; ?>
    </div>
</section>

<?php view('partials/next-quest', ['event' => $next]); ?>

<?php if ($upcomingEvents): ?>
<section id="upcoming" class="events-section">
    <div class="events-head">
        <div class="events-title">
            <mq-label>Demnächst</mq-label>
            <h2 class="heading-l">Diese Quests<br> stehen <span class="highlighted">an.</span></h2>
        </div>
    </div>
    <div class="events-grid">
<?php foreach ($upcomingEvents as $event): ?>
        <?php view('partials/event-card', ['event' => $event]); ?>
<?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($pastEvents): ?>
<section id="events" class="events-section">
    <div class="events-head">
        <div class="events-title">
            <mq-label>Vergangene Quests</mq-label>
            <h2 class="heading-l">Das haben wir<br> schon <span class="highlighted">erlebt.</span></h2>
        </div>
        <mq-event-filter for="events-grid"></mq-event-filter>
    </div>

    <div class="events-grid" id="events-grid">
<?php foreach ($pastEvents as $event): ?>
        <?php view('partials/event-card', ['event' => $event]); ?>
<?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- TODO: real numbers -->
<section id="numbers">
    <mq-label>Unsere Events in Zahlen</mq-label>
    <div class="stats">
        <mq-stat value="30+" heading="Events umgesetzt">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt.
        </mq-stat>
        <mq-stat value="16,4M Leute" heading="erreicht">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut enim ad minim veniam.
        </mq-stat>
    </div>
</section>

<?php view('partials/cta'); ?>

<?php view('partials/foot'); ?>
