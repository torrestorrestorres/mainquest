<?php view('partials/head', ['title' => 'Seite nicht gefunden']); ?>

<section id="hero" class="hero--compact">
    <div class="hero-content">
        <mq-label variant="light">404</mq-label>
        <h1 class="heading-xl">Diese Quest<br> gibt es nicht.</h1>
    </div>
    <div class="hero-footer">
        <p class="hero-text">Die Seite wurde verschoben oder existiert nicht mehr.</p>
        <a class="button" href="/">
            <span class="button-label">Zur Startseite</span>
            <span class="button-icon" aria-hidden="true">→</span>
        </a>
    </div>
</section>

<?php view('partials/foot'); ?>
