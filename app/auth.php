<?php
/*
 * Login, sessions, roles, CSRF protection, flash messages.
 *
 * Public pages start no session -> visitors get no cookie.
 * Only the admin area (and logged-in users) use one.
 */

declare(strict_types=1);

const SESSION_NAME = 'mq_admin';
const LOGIN_LIMIT_EMAIL = 5;     // failed logins per e-mail ...
const LOGIN_LIMIT_IP = 20;       // ... or per IP address ...
const LOGIN_WINDOW_MINUTES = 15; // ... within this time -> blocked

function session_begin(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = ($_SERVER['HTTPS'] ?? 'off') !== 'off' || str_starts_with((string) config('base_url'), 'https://');
    ini_set('session.use_strict_mode', '1');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // automatic logout after some hours without activity
    $maxIdle = (int) (config('session_idle_hours') ?? 8) * 3600;
    if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > $maxIdle) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = time();
}

/* Logged-in user or null. Only opens a session if the browser already has one. */
function current_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!isset($_COOKIE[SESSION_NAME])) {
            return null;
        }
        session_begin();
    }
    $id = $_SESSION['user_id'] ?? null;
    return $id ? user_find((int) $id) : null;
}

function require_login(): array
{
    session_begin();
    $user = current_user();
    if (!$user) {
        redirect('/admin/login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin/'));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Keine Berechtigung.');
    }
    return $user;
}

/* Returns the user on success, otherwise a German error message */
function attempt_login(string $email, string $password): array|string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (login_is_blocked($email, $ip)) {
        return 'Zu viele Fehlversuche. Bitte warte ' . LOGIN_WINDOW_MINUTES . ' Minuten.';
    }

    $user = user_find_by_email($email);
    // always verify a hash, so the response time doesn't reveal which e-mails exist
    $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    $valid = password_verify($password, $hash) && $user && $user['password_hash'];
    if (!$valid) {
        db()->prepare('INSERT INTO login_attempts (email, ip) VALUES (?, ?)')->execute([trim($email), $ip]);
        return 'E-Mail oder Passwort ist falsch.';
    }

    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        user_set_password((int) $user['id'], $password);
    }
    db()->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$user['email']]);
    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $user['id']]);

    login_user((int) $user['id']);
    return $user;
}

function login_user(int $userId): void
{
    session_begin();
    session_regenerate_id(true); // new session id after login (prevents session fixation)
    $_SESSION['user_id'] = $userId;
}

function login_is_blocked(string $email, string $ip): bool
{
    $since = '-' . LOGIN_WINDOW_MINUTES . ' minutes';
    $statement = db()->prepare("SELECT
        (SELECT COUNT(*) FROM login_attempts WHERE email = :email AND attempted_at > datetime('now', :since)),
        (SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at > datetime('now', :since))");
    $statement->execute(['email' => trim($email), 'ip' => $ip, 'since' => $since]);
    [$byEmail, $byIp] = $statement->fetch(PDO::FETCH_NUM);

    // clean up old attempts now and then
    if (random_int(1, 50) === 1) {
        db()->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-1 day')");
    }
    return $byEmail >= LOGIN_LIMIT_EMAIL || $byIp >= LOGIN_LIMIT_IP;
}

function logout(): void
{
    session_begin();
    $_SESSION = [];
    session_destroy();
    setcookie(SESSION_NAME, '', ['expires' => time() - 3600, 'path' => '/']);
}

/* ---------- CSRF: every admin form sends a secret token that only this session knows ---------- */

function csrf_token(): string
{
    session_begin();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    session_begin();
    // a too large upload arrives completely empty
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        exit('Die Dateien sind zu groß (maximal ' . ini_get('post_max_size') . ' pro Speichern). Bitte weniger Bilder auf einmal hochladen.');
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Das Formular ist abgelaufen. Bitte lade die Seite neu und versuche es nochmal.');
    }
}

/* ---------- flash messages: shown once on the next page ---------- */

function flash(string $type, string $message, ?string $link = null): void
{
    session_begin();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message, 'link' => $link];
}

/** @return array<int, array{type: string, message: string, link: ?string}> */
function take_flashes(): array
{
    session_begin();
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* Only redirect to our own admin pages (no open redirect via ?next=) */
function safe_admin_path(?string $path): string
{
    return ($path && preg_match('#^/admin/[^/\\\\]#', $path) || $path === '/admin/') ? $path : '/admin/';
}
