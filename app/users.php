<?php
/*
 * Users + one-time tokens (invite, password reset).
 * Tokens are random, only their SHA-256 hash is stored in the database.
 */

declare(strict_types=1);

const ROLES = ['admin' => 'Admin', 'editor' => 'Editor'];
const PASSWORD_MIN_LENGTH = 10;
const TOKEN_HOURS = ['invite' => 72, 'reset' => 2];

function user_find(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

function user_find_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE email = ?');
    $statement->execute([trim($email)]);
    return $statement->fetch() ?: null;
}

/** @return array<int, array> */
function users_all(): array
{
    return db()->query('SELECT * FROM users ORDER BY name COLLATE NOCASE, email')->fetchAll();
}

function users_count(?string $role = null): int
{
    $statement = db()->prepare('SELECT COUNT(*) FROM users WHERE ? IS NULL OR role = ?');
    $statement->execute([$role, $role]);
    return (int) $statement->fetchColumn();
}

function user_create(string $email, string $name, string $role, ?string $password = null): int
{
    $statement = db()->prepare('INSERT INTO users (email, name, role, password_hash) VALUES (?, ?, ?, ?)');
    $statement->execute([trim($email), trim($name), $role, $password ? password_hash($password, PASSWORD_DEFAULT) : null]);
    return (int) db()->lastInsertId();
}

function user_set_password(int $id, string $password): void
{
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}

function user_set_role(int $id, string $role): void
{
    db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
}

function user_delete(int $id): void
{
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}

/* German error message, or null if the password is fine */
function password_problem(string $password, string $confirmation): ?string
{
    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        return 'Das Passwort muss mindestens ' . PASSWORD_MIN_LENGTH . ' Zeichen lang sein.';
    }
    if ($password !== $confirmation) {
        return 'Die Passwörter stimmen nicht überein.';
    }
    return null;
}

/* Creates a one-time token (older unused ones of the same type become invalid) and returns it */
function token_create(int $userId, string $type): string
{
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + TOKEN_HOURS[$type] * 3600);

    db()->prepare('DELETE FROM user_tokens WHERE user_id = ? AND type = ? AND used_at IS NULL')
        ->execute([$userId, $type]);
    db()->prepare('INSERT INTO user_tokens (user_id, type, token_hash, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $type, hash('sha256', $token), $expiresAt]);

    return $token;
}

/* Valid (unused, not expired) token incl. its user, or null */
function token_find(string $token): ?array
{
    $statement = db()->prepare('SELECT user_tokens.id AS token_id, user_tokens.type, users.*
        FROM user_tokens JOIN users ON users.id = user_tokens.user_id
        WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?');
    $statement->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    return $statement->fetch() ?: null;
}

function token_use(int $tokenId): void
{
    db()->prepare("UPDATE user_tokens SET used_at = datetime('now') WHERE id = ?")->execute([$tokenId]);
}

function password_link(string $token): string
{
    return absolute_url('/admin/password.php?token=' . $token);
}

function user_has_open_invite(int $userId): bool
{
    $statement = db()->prepare("SELECT 1 FROM user_tokens WHERE user_id = ? AND type = 'invite' AND used_at IS NULL AND expires_at > ?");
    $statement->execute([$userId, date('Y-m-d H:i:s')]);
    return (bool) $statement->fetchColumn();
}
