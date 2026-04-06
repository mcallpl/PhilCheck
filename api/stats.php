<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireLoginAPI();

try {
    $db = getDB();

    $totalEntries = $db->query("SELECT COUNT(*) AS cnt FROM entries")->fetch_assoc()['cnt'];
    $totalChats = $db->query("SELECT COUNT(*) AS cnt FROM chat_history WHERE role='user'")->fetch_assoc()['cnt'];

    $firstRow = $db->query("SELECT created_at FROM entries ORDER BY created_at ASC LIMIT 1")->fetch_assoc();
    $firstEntry = $firstRow ? $firstRow['created_at'] : null;

    $latestRow = $db->query("SELECT created_at FROM entries ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
    $latestEntry = $latestRow ? $latestRow['created_at'] : null;

    // Entries per day for the last 30 days
    $daily = [];
    $result = $db->query("SELECT DATE(created_at) AS day, COUNT(*) AS count
        FROM entries
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC");
    while ($row = $result->fetch_assoc()) {
        $daily[] = $row;
    }

    echo json_encode([
        'success' => true,
        'total_entries' => $totalEntries,
        'total_chats' => $totalChats,
        'first_entry' => $firstEntry,
        'latest_entry' => $latestEntry,
        'daily_entries' => $daily
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
