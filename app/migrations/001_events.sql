-- Events + their gallery images

CREATE TABLE events (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT    NOT NULL UNIQUE,          -- URL: /events/<slug>
    title        TEXT    NOT NULL,
    category     TEXT    NOT NULL DEFAULT '',      -- e.g. Creator-Event, Brand Activation (filter chips)
    location     TEXT    NOT NULL DEFAULT '',
    starts_at    TEXT    NOT NULL,                 -- "2026-10-31 19:00", decides upcoming / past
    teaser       TEXT    NOT NULL DEFAULT '',      -- short text on the cards
    body         TEXT    NOT NULL DEFAULT '',      -- long text on the detail page (blank line = paragraph)
    cover_image  TEXT,                             -- URL path, e.g. /uploads/events/<slug>/cover.webp
    cover_alt    TEXT    NOT NULL DEFAULT '',
    recap_url    TEXT,                             -- aftermovie / instagram (past events)
    ticket_url   TEXT,                             -- tickets / registration (upcoming events)
    is_published INTEGER NOT NULL DEFAULT 0,       -- drafts are only visible in the admin
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX events_starts_at ON events (starts_at);

CREATE TABLE event_images (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER NOT NULL REFERENCES events (id) ON DELETE CASCADE,
    path     TEXT    NOT NULL,
    alt      TEXT    NOT NULL DEFAULT '',
    position INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX event_images_event ON event_images (event_id, position);
