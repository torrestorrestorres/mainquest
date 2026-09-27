<?php
/*
 * Create / edit / delete an event incl. cover and gallery.
 *   /admin/event.php        -> new event
 *   /admin/event.php?id=12  -> edit event 12
 */
require __DIR__ . '/../../app/bootstrap.php';

$user = require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$event = $id ? event_find($id) : null;
if ($id && !$event) {
    not_found();
}

$errors = [];
$input = $event ?? [
    'title' => '', 'slug' => '', 'category' => '', 'location' => '', 'starts_at' => '',
    'teaser' => '', 'body' => '', 'cover_image' => null, 'cover_alt' => '',
    'recap_url' => '', 'ticket_url' => '', 'is_published' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // ---------- delete ----------
    if (($_POST['action'] ?? '') === 'delete' && $event) {
        event_delete($id);
        image_delete($event['cover_image']);
        image_delete_folder(event_upload_folder($id));
        flash('success', "„{$event['title']}“ wurde gelöscht.");
        redirect('/admin/');
    }

    // ---------- read + check the form ----------
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '') ?: '00:00';
    $input = array_merge($input, [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => slugify(trim($_POST['slug'] ?? '') ?: trim($_POST['title'] ?? '')),
        'category' => trim($_POST['category'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'starts_at' => "{$date} {$time}",
        'teaser' => trim($_POST['teaser'] ?? ''),
        'body' => trim($_POST['body'] ?? ''),
        'cover_alt' => trim($_POST['cover_alt'] ?? ''),
        'recap_url' => trim($_POST['recap_url'] ?? ''),
        'ticket_url' => trim($_POST['ticket_url'] ?? ''),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ]);

    if ($input['title'] === '') {
        $errors[] = 'Bitte gib einen Titel ein.';
    }
    if (!DateTime::createFromFormat('!Y-m-d H:i', $input['starts_at'])) {
        $errors[] = 'Bitte gib ein gültiges Datum an.';
    }
    if ($input['slug'] === '') {
        $errors[] = 'Die Adresse (Slug) darf nicht leer sein.';
    }
    foreach (['recap_url' => 'Recap-Link', 'ticket_url' => 'Ticket-Link'] as $field => $label) {
        if ($input[$field] !== '' && !is_http_url($input[$field])) {
            $errors[] = "{$label}: Bitte eine vollständige Adresse mit https:// angeben.";
        }
    }

    // ---------- save ----------
    if (!$errors) {
        $input['slug'] = event_unique_slug($input['slug'], $id);
        $input['recap_url'] = $input['recap_url'] ?: null;
        $input['ticket_url'] = $input['ticket_url'] ?: null;
        $id = event_save($input, $id);
        $folder = event_upload_folder($id);
        $imageErrors = [];

        // cover: new upload replaces the old one
        foreach (uploaded_files('cover') as $file) {
            try {
                $path = image_store_upload($file, $folder);
                image_delete($event['cover_image'] ?? null);
                event_set_cover($id, $path);
            } catch (RuntimeException $error) {
                $imageErrors[] = $error->getMessage();
            }
        }

        // gallery: change alt texts + order, remove selected images
        foreach ($_POST['images'] ?? [] as $imageId => $image) {
            if (!empty($image['remove'])) {
                image_delete(event_image_remove($id, (int) $imageId));
            } else {
                event_image_update($id, (int) $imageId, trim($image['alt'] ?? ''), (int) ($image['position'] ?? 0));
            }
        }

        // gallery: new images are added at the end
        foreach (uploaded_files('gallery') as $file) {
            try {
                event_image_add($id, image_store_upload($file, $folder));
            } catch (RuntimeException $error) {
                $imageErrors[] = $error->getMessage();
            }
        }

        flash('success', 'Gespeichert.');
        foreach ($imageErrors as $message) {
            flash('error', $message);
        }
        redirect("/admin/event.php?id={$id}");
    }
}

$images = $event ? event_images((int) $event['id']) : [];
[$dateValue, $timeValue] = array_pad(explode(' ', (string) $input['starts_at']), 2, '');
$title = $event ? $event['title'] : 'Neues Event';

view('admin/head', ['title' => $title, 'user' => $user, 'section' => 'events']);
?>
<div class="admin-toolbar">
    <h1><?= e($title) ?></h1>
<?php if ($event): ?>
    <a class="btn btn--ghost" href="<?= e(event_url($event)) ?>" target="_blank">
        <?= $event['is_published'] ? 'Ansehen ↗' : 'Vorschau ↗' ?>
    </a>
<?php endif; ?>
</div>

<?php view('admin/errors', ['errors' => $errors]); ?>

<form class="form event-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <section class="admin-card">
        <h2>Allgemein</h2>
        <label class="field">Titel
            <input type="text" name="title" value="<?= e($input['title']) ?>" required maxlength="120">
        </label>
        <div class="field-row">
            <label class="field">Datum
                <input type="date" name="date" value="<?= e($dateValue) ?>" required>
            </label>
            <label class="field">Uhrzeit <small>(optional)</small>
                <input type="time" name="time" value="<?= e($timeValue !== '00:00' ? $timeValue : '') ?>">
            </label>
        </div>
        <div class="field-row">
            <label class="field">Ort
                <input type="text" name="location" value="<?= e($input['location']) ?>" maxlength="80">
            </label>
            <label class="field">Kategorie <small>(wird zum Filter auf der Events-Seite)</small>
                <input type="text" name="category" value="<?= e($input['category']) ?>" list="categories" maxlength="40">
                <datalist id="categories">
<?php foreach (event_categories() as $category): ?>
                    <option value="<?= e($category) ?>">
<?php endforeach; ?>
                </datalist>
            </label>
        </div>
        <label class="field">Adresse <small>(leer lassen = automatisch aus dem Titel)</small>
            <span class="input-prefix"><span>/events/</span><input type="text" name="slug" value="<?= e($input['slug']) ?>" pattern="[a-z0-9\-]*" maxlength="120"></span>
        </label>
        <label class="checkbox">
            <input type="checkbox" name="is_published" value="1" <?= $input['is_published'] ? 'checked' : '' ?>>
            Veröffentlicht <small class="muted">(sonst nur als Vorschau für eingeloggte Benutzer sichtbar)</small>
        </label>
    </section>

    <section class="admin-card">
        <h2>Texte</h2>
        <label class="field">Kurztext <small>(auf den Karten und in der Link-Vorschau, max. 220 Zeichen)</small>
            <textarea name="teaser" rows="3" maxlength="220"><?= e($input['teaser']) ?></textarea>
        </label>
        <label class="field">Beschreibung <small>(Detailseite. Leerzeile = neuer Absatz)</small>
            <textarea name="body" rows="10"><?= e($input['body']) ?></textarea>
        </label>
    </section>

    <section class="admin-card">
        <h2>Links</h2>
        <label class="field">Tickets / Anmeldung <small>(Button bei anstehenden Events)</small>
            <input type="url" name="ticket_url" value="<?= e($input['ticket_url']) ?>" placeholder="https://…">
        </label>
        <label class="field">Aftermovie / Recap <small>(Button bei vergangenen Events)</small>
            <input type="url" name="recap_url" value="<?= e($input['recap_url']) ?>" placeholder="https://…">
        </label>
    </section>

    <section class="admin-card">
        <h2>Titelbild</h2>
        <div class="cover-field">
            <img class="cover-preview" src="<?= e(event_cover($input)) ?>" alt="" data-preview="cover">
            <div class="cover-inputs">
                <label class="field"><?= $input['cover_image'] ? 'Neues Bild hochladen' : 'Bild hochladen' ?>
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/avif" data-preview-target="cover">
                </label>
                <label class="field">Bildbeschreibung <small>(für Screenreader und Google)</small>
                    <input type="text" name="cover_alt" value="<?= e($input['cover_alt']) ?>" maxlength="160">
                </label>
                <p class="muted small">Bilder werden automatisch verkleinert und optimiert – du kannst Fotos direkt vom Handy oder der Kamera hochladen.</p>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <h2>Galerie <small class="muted">(Detailseite)</small></h2>
<?php if ($images): ?>
        <ul class="gallery-list">
<?php foreach ($images as $image): ?>
            <li class="gallery-item">
                <img src="<?= e($image['path']) ?>" alt="" loading="lazy">
                <label class="field">Beschreibung
                    <input type="text" name="images[<?= (int) $image['id'] ?>][alt]" value="<?= e($image['alt']) ?>" maxlength="160">
                </label>
                <label class="field field--small">Reihenfolge
                    <input type="number" name="images[<?= (int) $image['id'] ?>][position]" value="<?= (int) $image['position'] ?>" min="0">
                </label>
                <label class="checkbox checkbox--danger">
                    <input type="checkbox" name="images[<?= (int) $image['id'] ?>][remove]" value="1"> Entfernen
                </label>
            </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
        <label class="field">Bilder hinzufügen <small>(mehrere auswählbar)</small>
            <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/avif" multiple>
        </label>
    </section>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Speichern</button>
        <a class="btn btn--ghost" href="/admin/">Abbrechen</a>
    </div>
</form>

<?php if ($event): ?>
<form class="danger-zone" method="post" data-confirm="„<?= e($event['title']) ?>“ wirklich löschen? Das kann nicht rückgängig gemacht werden.">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <button class="btn btn--danger" type="submit">Event löschen</button>
</form>
<?php endif; ?>

<?php view('admin/foot'); ?>
