<?php
/* Site settings (admins only): contact e-mails + social media links */
require __DIR__ . '/../../app/bootstrap.php';

$user = require_admin();
$errors = [];

$fields = ['contact_email', 'contact_recipients'];
foreach (array_keys(SOCIAL_NETWORKS) as $network) {
    $fields[] = "social_{$network}";
}
$values = array_combine($fields, array_map(fn($field) => setting($field), $fields));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($fields as $field) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if ($values['contact_email'] !== '' && !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Die öffentliche E-Mail-Adresse ist ungültig.';
    }
    foreach (array_filter(array_map('trim', explode(',', $values['contact_recipients']))) as $recipient) {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Empfänger „{$recipient}“ ist keine gültige E-Mail-Adresse.";
        }
    }
    foreach (SOCIAL_NETWORKS as $network => $label) {
        $url = $values["social_{$network}"];
        if ($url !== '' && !is_http_url($url)) {
            $errors[] = "{$label}: Bitte die vollständige Adresse mit https:// angeben.";
        }
    }

    if (!$errors) {
        settings_save($values);
        flash('success', 'Einstellungen gespeichert.');
        redirect('/admin/settings.php');
    }
}

view('admin/head', ['title' => 'Einstellungen', 'user' => $user, 'section' => 'settings']);
?>
<div class="admin-toolbar">
    <h1>Einstellungen</h1>
</div>

<?php view('admin/errors', ['errors' => $errors]); ?>

<form class="form" method="post">
    <?= csrf_field() ?>

    <section class="admin-card">
        <h2>Kontakt</h2>
        <label class="field">Öffentliche E-Mail-Adresse <small>(wird auf der Kontaktseite angezeigt)</small>
            <input type="email" name="contact_email" value="<?= e($values['contact_email']) ?>" placeholder="info@…">
        </label>
        <label class="field">Benachrichtigung bei neuen Anfragen an <small>(mehrere mit Komma trennen)</small>
            <input type="text" name="contact_recipients" value="<?= e($values['contact_recipients']) ?>" placeholder="finja@…, marius@…">
        </label>
<?php if (empty(config('mail')['enabled'])): ?>
        <p class="muted small">Hinweis: Der Mailversand ist in dieser Umgebung ausgeschaltet (config.php), Mails landen in data/mail.log.</p>
<?php endif; ?>
    </section>

    <section class="admin-card">
        <h2>Social Media <small class="muted">(leere Felder werden nicht angezeigt)</small></h2>
<?php foreach (SOCIAL_NETWORKS as $network => $label): ?>
        <label class="field"><?= e($label) ?>
            <input type="url" name="social_<?= $network ?>" value="<?= e($values["social_{$network}"]) ?>" placeholder="https://…">
        </label>
<?php endforeach; ?>
    </section>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Speichern</button>
    </div>
</form>

<?php view('admin/foot'); ?>
