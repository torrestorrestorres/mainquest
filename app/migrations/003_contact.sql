-- Contact form messages + editable site settings (e-mail recipients, social links, …)

CREATE TABLE contact_messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    company    TEXT NOT NULL DEFAULT '',
    topic      TEXT NOT NULL DEFAULT '',                -- key of CONTACT_TOPICS
    message    TEXT NOT NULL,
    status     TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'open', 'done')),
    ip_hash    TEXT NOT NULL DEFAULT '',                -- only for the rate limit, not the real IP
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX contact_messages_status ON contact_messages (status, created_at);
CREATE INDEX contact_messages_ip ON contact_messages (ip_hash, created_at);

CREATE TABLE settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);
