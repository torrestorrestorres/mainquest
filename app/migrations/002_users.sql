-- Admin users, invite / password reset tokens, failed logins

CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
    name          TEXT    NOT NULL DEFAULT '',
    role          TEXT    NOT NULL DEFAULT 'editor' CHECK (role IN ('admin', 'editor')),
    password_hash TEXT,                              -- NULL until the invite was accepted
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    last_login_at TEXT
);

-- one-time links for invites and "forgot password" (only the hash is stored)
CREATE TABLE user_tokens (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    type       TEXT    NOT NULL CHECK (type IN ('invite', 'reset')),
    token_hash TEXT    NOT NULL UNIQUE,
    expires_at TEXT    NOT NULL,
    used_at    TEXT
);

-- failed logins, used to block brute force attempts for a while
CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    email        TEXT NOT NULL COLLATE NOCASE,
    ip           TEXT NOT NULL,
    attempted_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX login_attempts_email ON login_attempts (email, attempted_at);
CREATE INDEX login_attempts_ip ON login_attempts (ip, attempted_at);
