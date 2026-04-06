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
        role VARCHAR(20) DEFAULT 'user',
        login_count INT DEFAULT 0,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add role column if missing (upgrade path)
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($check->num_rows === 0) {
        $db->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER password_hash");
    }

    // Seed default users if table is empty
    $result = $db->query("SELECT COUNT(*) AS cnt FROM users");
    $row = $result->fetch_assoc();
    if ($row['cnt'] == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");

        $user = 'phil';
        $hash = password_hash('poppers', PASSWORD_DEFAULT);
        $role = 'user';
        $stmt->bind_param('sss', $user, $hash, $role);
        $stmt->execute();

        $user = 'mcallpl';
        $hash = password_hash('amazing', PASSWORD_DEFAULT);
        $role = 'admin';
        $stmt->bind_param('sss', $user, $hash, $role);
        $stmt->execute();
        $stmt->close();
    } else {
        // Ensure existing users have correct roles
        $db->query("UPDATE users SET role = 'admin' WHERE username = 'mcallpl' AND (role IS NULL OR role = 'user')");
        $db->query("UPDATE users SET role = 'user' WHERE username = 'phil' AND role IS NULL");
    }
}
