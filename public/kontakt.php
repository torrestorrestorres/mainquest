<?php
/* /kontakt: contact form + direct contact + social links. ?anliegen=event preselects the topic. */
require __DIR__ . '/../app/bootstrap.php';

$values = [
    'name' => '', 'email' => '', 'company' => '', 'message' => '',
    'topic' => array_key_exists($_GET['anliegen'] ?? '', CONTACT_TOPICS) ? $_GET['anliegen'] : 'event',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$values, $errors, $isSpam] = contact_validate($_POST);

    // bots get the normal "thank you" page, but nothing is saved or sent
    if ($isSpam) {
        redirect('/kontakt?gesendet=1#kontakt');
    }
    if (!$errors && contact_rate_limited()) {
        $errors['form'] = 'Du hast gerade schon einige Nachrichten geschickt. Bitte versuch es später nochmal oder schreib uns direkt per Mail.';
    }
    if (!$errors) {
        contact_notify(contact_save($values), $values);
        redirect('/kontakt?gesendet=1#kontakt');
    }
}

$sent = isset($_GET['gesendet']);
$contactEmail = setting('contact_email', (string) (config('mail')['reply_to'] ?? ''));

/* aria attributes + error text for a form field */
$invalid = fn(string $field) => isset($errors[$field]) ? " aria-invalid=\"true\" aria-describedby=\"error-{$field}\"" : '';
$error = fn(string $field) => isset($errors[$field]) ? "<p class=\"form-error\" id=\"error-{$field}\">" . e($errors[$field]) . '</p>' : '';

view('partials/head', [
    'title' => 'Kontakt',
    'description' => 'Du planst ein Event, bist Creator oder suchst eine Brand Activation? Schreib MainQuest.',
]);
?>

<section id="hero" class="hero--compact" style="--hero-image: url('/imgs/vibes/nightclub-web.jpg')">
    <div class="hero-content">
        <mq-label variant="light">Kontakt</mq-label>
        <h1 class="heading-xl">Lass uns deine<br> Quest planen.</h1>
    </div>
    <div class="hero-footer">
        <p class="hero-text">
            Egal ob Creator-Event, Community Experience oder Brand Activation:
            Erzähl uns von deiner Idee, wir melden uns schnellstmöglich bei dir.
        </p>
    </div>
</section>

<section id="kontakt" class="contact-section">
    <div class="contact-main">
<?php if ($sent): ?>
        <div class="contact-success" role="status">
            <mq-label variant="light">Nachricht gesendet</mq-label>
            <h2 class="heading-m">Danke! Deine Nachricht<br> ist bei uns angekommen.</h2>
            <p>Wir melden uns so schnell wie möglich bei dir – meistens innerhalb von zwei Werktagen.</p>
            <a class="link-arrow dashed-line" href="/events"><span aria-hidden="true">→</span> Solange: unsere Events</a>
        </div>
<?php else: ?>
        <form class="contact-form" method="post" action="/kontakt#kontakt">
            <fieldset class="topic-chips" role="radiogroup"<?= $invalid('topic') ?>>
                <legend>Worum geht's?</legend>
<?php foreach (CONTACT_TOPICS as $key => $label): ?>
                <label class="chip-radio">
                    <input type="radio" name="topic" value="<?= $key ?>" <?= $values['topic'] === $key ? 'checked' : '' ?> required>
                    <span class="filter-chip"><?= e($label) ?></span>
                </label>
<?php endforeach; ?>
                <?= $error('topic') ?>
            </fieldset>

            <div class="form-row">
                <label class="form-field">Name
                    <input type="text" name="name" value="<?= e($values['name']) ?>" required maxlength="100" autocomplete="name"<?= $invalid('name') ?>>
                    <?= $error('name') ?>
                </label>
                <label class="form-field">E-Mail
                    <input type="email" name="email" value="<?= e($values['email']) ?>" required maxlength="200" autocomplete="email"<?= $invalid('email') ?>>
                    <?= $error('email') ?>
                </label>
            </div>

            <label class="form-field">Firma / Kanal <small>(optional)</small>
                <input type="text" name="company" value="<?= e($values['company']) ?>" maxlength="150" autocomplete="organization">
            </label>

            <label class="form-field">Deine Nachricht
                <textarea name="message" rows="6" required maxlength="<?= CONTACT_MESSAGE_MAX ?>"
                          placeholder="Was hast du vor? Zeitraum, Ort, Anzahl Gäste …"<?= $invalid('message') ?>><?= e($values['message']) ?></textarea>
                <?= $error('message') ?>
            </label>

            <!-- spam protection: humans never see or fill this field -->
            <div class="hp" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>
            <input type="hidden" name="t" value="<?= e(contact_time_token()) ?>">

            <label class="form-check">
                <input type="checkbox" name="privacy" value="1" required<?= $invalid('privacy') ?>>
                <span>
                    Ich bin einverstanden, dass meine Angaben zur Beantwortung meiner Anfrage gespeichert werden.
                    <!-- TODO: privacy policy page -->
                    Mehr dazu in der <a href="/datenschutz">Datenschutzerklärung</a>.
                </span>
            </label>
            <?= $error('privacy') ?>
            <?= $error('form') ?>

            <button class="button button--dark" type="submit">
                <span class="button-label">Nachricht senden</span>
                <span class="button-icon" aria-hidden="true">→</span>
            </button>
        </form>
<?php endif; ?>
    </div>

    <aside class="contact-info">
<?php if ($contactEmail !== ''): ?>
        <div class="contact-block">
            <mq-label>Direkter Draht</mq-label>
            <!-- <wbr>: long addresses wrap after the @ instead of in the middle of a word -->
            <a class="contact-email" href="mailto:<?= e($contactEmail) ?>"><?= str_replace('@', '@<wbr>', e($contactEmail)) ?></a>
        </div>
<?php endif; ?>
<?php if ($socials = social_links()): ?>
        <div class="contact-block">
            <mq-label>Folg unseren Quests</mq-label>
            <ul class="social-list">
<?php foreach ($socials as $social): ?>
                <li>
                    <a class="link-arrow link-arrow--small dashed-line" href="<?= e($social['url']) ?>" target="_blank" rel="noopener">
                        <span aria-hidden="true">→</span> <?= e($social['label']) ?>
                    </a>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
<?php endif; ?>
    </aside>
</section>

<?php view('partials/foot'); ?>
