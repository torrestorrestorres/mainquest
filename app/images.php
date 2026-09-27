<?php
/*
 * Image uploads: every image is checked, turned upright (phone photos),
 * scaled down and saved as WebP - no matter how big the original was.
 */

declare(strict_types=1);

const IMAGE_MAX_BYTES = 25 * 1024 * 1024;   // largest accepted upload
const IMAGE_MAX_SIDE = 2000;                // longest side after scaling (px)
const IMAGE_QUALITY = 80;                   // WebP quality 0-100
const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

/* Saves an uploaded file into uploads/<folder>/ and returns its URL path. Throws with a German message. */
function image_store_upload(array $file, string $folder): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($file['error'], $file['name']));
    }
    if ($file['size'] > IMAGE_MAX_BYTES) {
        throw new RuntimeException("„{$file['name']}“ ist zu groß (max. " . (IMAGE_MAX_BYTES / 1024 / 1024) . ' MB).');
    }

    // check the real content, not the file name
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, IMAGE_TYPES, true)) {
        throw new RuntimeException("„{$file['name']}“ ist kein unterstütztes Bild (JPG, PNG, WebP, AVIF).");
    }

    $image = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$image) {
        throw new RuntimeException("„{$file['name']}“ konnte nicht gelesen werden.");
    }

    // scale first, then rotate: rotating a small image needs much less memory
    $image = image_scale_down($image, IMAGE_MAX_SIDE);
    $image = image_fix_orientation($image, $file['tmp_name'], $mime);

    $directory = rtrim((string) config('uploads_path'), '/') . '/' . $folder;
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $name = bin2hex(random_bytes(8)) . '.webp';

    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagewebp($image, "{$directory}/{$name}", IMAGE_QUALITY);
    imagedestroy($image);

    return rtrim((string) config('uploads_url'), '/') . "/{$folder}/{$name}";
}

function image_scale_down(GdImage $image, int $maxSide): GdImage
{
    $width = imagesx($image);
    $height = imagesy($image);
    $scale = $maxSide / max($width, $height);
    if ($scale >= 1) {
        return $image;
    }
    $scaled = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
    imagedestroy($image);
    return $scaled;
}

/* Phone photos store their rotation in the EXIF data instead of rotating the pixels */
function image_fix_orientation(GdImage $image, string $path, string $mime): GdImage
{
    if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
        return $image;
    }
    $angle = match ((int) (@exif_read_data($path)['Orientation'] ?? 1)) {
        3 => 180,
        6 => -90,
        8 => 90,
        default => 0,
    };
    if ($angle === 0) {
        return $image;
    }
    $rotated = imagerotate($image, $angle, 0);
    imagedestroy($image);
    return $rotated;
}

/* Deletes an uploaded image. Files outside the uploads folder (e.g. demo images in /imgs) are never touched. */
function image_delete(?string $urlPath): void
{
    $prefix = rtrim((string) config('uploads_url'), '/') . '/';
    if (!$urlPath || !str_starts_with($urlPath, $prefix)) {
        return;
    }
    $base = realpath((string) config('uploads_path'));
    $file = realpath($base . '/' . substr($urlPath, strlen($prefix)));
    if ($base && $file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)) {
        unlink($file);
    }
}

/* Removes an upload folder incl. all files (e.g. when an event is deleted) */
function image_delete_folder(string $folder): void
{
    $base = realpath((string) config('uploads_path'));
    $directory = realpath($base . '/' . $folder);
    if (!$base || !$directory || !str_starts_with($directory, $base . DIRECTORY_SEPARATOR)) {
        return;
    }
    array_map('unlink', glob($directory . '/*') ?: []);
    rmdir($directory);
}

/** $_FILES['x'] of an <input multiple> -> list of single files (empty fields skipped) */
function uploaded_files(string $field): array
{
    $input = $_FILES[$field] ?? null;
    if (!$input) {
        return [];
    }
    if (!is_array($input['name'])) {
        return $input['error'] === UPLOAD_ERR_NO_FILE ? [] : [$input];
    }
    $files = [];
    foreach (array_keys($input['name']) as $i) {
        if ($input['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = array_combine(array_keys($input), array_column($input, $i));
    }
    return $files;
}

function upload_error_message(int $error, string $name): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "„{$name}“ ist zu groß (max. " . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_PARTIAL => "„{$name}“ wurde nur teilweise hochgeladen, bitte nochmal versuchen.",
        default => "„{$name}“ konnte nicht hochgeladen werden (Fehler {$error}).",
    };
}
