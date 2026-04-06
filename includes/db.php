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

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        login_count INTEGER DEFAULT 0,
        last_login DATETIME,
        created_at DATETIME DEFAULT (datetime('now','localtime'))
    )");

    // Seed default users if table is empty
    $userCount = $db->querySingle("SELECT COUNT(*) FROM users");
    if ($userCount == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (:u, :p)");
        $stmt->bindValue(':u', 'phil', SQLITE3_TEXT);
        $stmt->bindValue(':p', password_hash('poppers', PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->execute();

        $stmt->bindValue(':u', 'mcallpl', SQLITE3_TEXT);
        $stmt->bindValue(':p', password_hash('amazing', PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->execute();
    }

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
