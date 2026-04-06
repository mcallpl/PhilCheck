<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireAdminAPI();

try {
    $db = getDB();

    // All users with login info
    $users = [];
    $result = $db->query("SELECT id, username, role, login_count, last_login, created_at FROM users ORDER BY role DESC, username ASC");
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    // Entry stats
    $totalEntries = $db->query("SELECT COUNT(*) AS cnt FROM entries")->fetch_assoc()['cnt'];
    $totalChats = $db->query("SELECT COUNT(*) AS cnt FROM chat_history WHERE role='user'")->fetch_assoc()['cnt'];
    $totalInsights = $db->query("SELECT COUNT(*) AS cnt FROM chat_history WHERE role='insight'")->fetch_assoc()['cnt'];

    $firstRow = $db->query("SELECT created_at FROM entries ORDER BY created_at ASC LIMIT 1")->fetch_assoc();
    $firstEntry = $firstRow ? $firstRow['created_at'] : null;

    $latestRow = $db->query("SELECT created_at FROM entries ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
    $latestEntry = $latestRow ? $latestRow['created_at'] : null;

    // Entries per day (last 30 days)
    $daily = [];
    $result = $db->query("SELECT DATE(created_at) AS day, COUNT(*) AS count
        FROM entries
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC");
    while ($row = $result->fetch_assoc()) {
        $daily[] = $row;
    }

    // Entry sources breakdown
    $sources = [];
    $result = $db->query("SELECT source_type, COUNT(*) AS count FROM entries GROUP BY source_type ORDER BY count DESC");
    while ($row = $result->fetch_assoc()) {
        $sources[] = $row;
    }

    // Recent entries (last 5)
    $recentEntries = [];
    $result = $db->query("SELECT id, LEFT(content, 200) AS preview, source_type, source_name, created_at FROM entries ORDER BY created_at DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $recentEntries[] = $row;
    }

    // Recent chat questions (last 10)
    $recentChats = [];
    $result = $db->query("SELECT message, created_at FROM chat_history WHERE role='user' ORDER BY id DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $recentChats[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total_entries' => $totalEntries,
        'total_chats' => $totalChats,
        'total_insights' => $totalInsights,
        'first_entry' => $firstEntry,
        'latest_entry' => $latestEntry,
        'daily_entries' => $daily,
        'sources' => $sources,
        'recent_entries' => $recentEntries,
        'recent_chats' => $recentChats
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
