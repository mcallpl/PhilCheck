<?php
require_once __DIR__ . '/db.php';

session_start();

function requireLogin() {
    // Check if logged in
    if (empty($_SESSION['user_id'])) {
        redirectToLogin();
        return;
    }

    // Check session timeout (10 minutes of inactivity)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_message'] = 'You were logged out due to inactivity.';
        redirectToLogin();
        return;
    }

    // Update last activity
    $_SESSION['last_activity'] = time();
}

function requireLoginAPI() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated', 'redirect' => 'login.php']);
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Session expired', 'redirect' => 'login.php']);
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function redirectToLogin() {
    // For API calls, return JSON
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    header('Location: login.php');
    exit;
}

function getCurrentUser() {
    if (empty($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, login_count, last_login FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}
