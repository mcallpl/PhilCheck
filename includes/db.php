<?php
require_once __DIR__ . '/../config.php';

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            die('Database connection failed: ' . $db->connect_error);
        }
        $db->set_charset('utf8mb4');
        initDB($db);
    }
    return $db;
}

function initDB($db) {
    $db->query("CREATE TABLE IF NOT EXISTS entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content LONGTEXT NOT NULL,
        source_type VARCHAR(50) DEFAULT 'text',
        source_name VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        tags VARCHAR(500) DEFAULT '',
        FULLTEXT KEY ft_content (content)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS chat_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        message LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        login_count INT DEFAULT 0,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default users if table is empty
    $result = $db->query("SELECT COUNT(*) AS cnt FROM users");
    $row = $result->fetch_assoc();
    if ($row['cnt'] == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $hash = password_hash('poppers', PASSWORD_DEFAULT);
        $user = 'phil';
        $stmt->bind_param('ss', $user, $hash);
        $stmt->execute();

        $hash = password_hash('amazing', PASSWORD_DEFAULT);
        $user = 'mcallpl';
        $stmt->bind_param('ss', $user, $hash);
        $stmt->execute();
        $stmt->close();
    }
}
