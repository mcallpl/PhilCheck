<?php
require_once __DIR__ . '/../config.php';

function getDB() {
    static $db = null;
    if ($db === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $db = new SQLite3(DB_PATH);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');
        initDB($db);
    }
    return $db;
}

function initDB($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        content TEXT NOT NULL,
        source_type TEXT DEFAULT 'text',
        source_name TEXT,
        created_at DATETIME DEFAULT (datetime('now','localtime')),
        tags TEXT DEFAULT ''
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS chat_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role TEXT NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT (datetime('now','localtime'))
    )");

    $db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS entries_fts USING fts5(content, content='entries', content_rowid='id')");

    // Triggers to keep FTS in sync
    $db->exec("CREATE TRIGGER IF NOT EXISTS entries_ai AFTER INSERT ON entries BEGIN
        INSERT INTO entries_fts(rowid, content) VALUES (new.id, new.content);
    END");
    $db->exec("CREATE TRIGGER IF NOT EXISTS entries_ad AFTER DELETE ON entries BEGIN
        INSERT INTO entries_fts(entries_fts, rowid, content) VALUES('delete', old.id, old.content);
    END");
    $db->exec("CREATE TRIGGER IF NOT EXISTS entries_au AFTER UPDATE ON entries BEGIN
        INSERT INTO entries_fts(entries_fts, rowid, content) VALUES('delete', old.id, old.content);
        INSERT INTO entries_fts(rowid, content) VALUES (new.id, new.content);
    END");
}
