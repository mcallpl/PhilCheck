<?php
require_once __DIR__ . '/db.php';

session_start();

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        redirectToLogin();
        return;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_message'] = 'You were logged out due to inactivity.';
        redirectToLogin();
        return;
    }

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
    $stmt = $db->prepare("SELECT id, username, login_count, last_login FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}
