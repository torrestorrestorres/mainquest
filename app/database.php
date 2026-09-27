<?php
/*
 * SQLite connection + automatic migrations.
 *
 * Schema changes = new file in app/migrations (e.g. 002_users.sql).
 * Every file runs exactly once, on the first request after a deploy.
 * Never change a migration that already ran - add a new one instead.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $path = (string) config('db_path');
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
        name       TEXT PRIMARY KEY,
        applied_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
    )');

    $files = glob(APP_PATH . '/migrations/*.sql') ?: [];
    sort($files);

    $applied = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    $pending = array_filter($files, fn($file) => !in_array(basename($file), $applied, true));
    if (!$pending) {
        return;
    }

    // IMMEDIATE = lock the database, so two requests can't run the same migration
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $applied = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $insert = $pdo->prepare('INSERT INTO migrations (name) VALUES (?)');

        foreach ($pending as $file) {
            if (in_array(basename($file), $applied, true)) {
                continue;
            }
            $pdo->exec(file_get_contents($file));
            $insert->execute([basename($file)]);
        }
        $pdo->exec('COMMIT');
    } catch (Throwable $error) {
        $pdo->exec('ROLLBACK');
        throw $error;
    }
}
