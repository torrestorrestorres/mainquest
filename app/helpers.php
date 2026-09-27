<?php
/*
 * Small helpers used in pages and views.
 */

declare(strict_types=1);

/* Escape for HTML output. Use for EVERY value that comes from the database or the user. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* URL of a file in public/ with a version parameter, so browsers load changed files right away */
function asset(string $path): string
{
    $path = '/' . ltrim($path, '/');
    $file = PUBLIC_PATH . $path;
    return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}

/* Full URL (share previews need absolute URLs) */
function absolute_url(string $path): string
{
    return rtrim((string) config('base_url'), '/') . '/' . ltrim($path, '/');
}

/* Image path for use inside CSS url('…'): only harmless characters are allowed */
function css_url(?string $path): string
{
    return ($path && preg_match('#^/[A-Za-z0-9/_.\-]+$#', $path)) ? $path : '';
}

/* Renders app/views/<name>.php with the given variables */
function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_PATH . "/views/{$name}.php";
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function not_found(): never
{
    http_response_code(404);
    view('404');
    exit;
}

/* "2026-10-31 19:00" -> "31.10.2026" */
function format_date(string $dateTime): string
{
    return date('d.m.Y', strtotime($dateTime));
}

/* Plain text -> escaped paragraphs (blank line = new paragraph, line break = <br>) */
function paragraphs(?string $text): string
{
    $blocks = preg_split('/\R{2,}/', trim($text ?? '')) ?: [];
    return implode('', array_map(
        fn($block) => '<p>' . nl2br(e(trim($block))) . '</p>',
        array_filter($blocks, fn($block) => trim($block) !== '')
    ));
}

/* Only complete http(s) links (no javascript:, no relative paths) */
function is_http_url(string $url): bool
{
    return (bool) preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/* "Halloween Creator Night 2025" -> "halloween-creator-night-2025" */
function slugify(string $text): string
{
    $text = strtr(mb_strtolower($text), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}
