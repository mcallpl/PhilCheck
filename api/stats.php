<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();

    $totalEntries = $db->querySingle("SELECT COUNT(*) FROM entries");
    $totalChats = $db->querySingle("SELECT COUNT(*) FROM chat_history WHERE role='user'");
    $firstEntry = $db->querySingle("SELECT created_at FROM entries ORDER BY created_at ASC LIMIT 1");
    $latestEntry = $db->querySingle("SELECT created_at FROM entries ORDER BY created_at DESC LIMIT 1");

    // Entries per day for the last 30 days
    $daily = [];
    $result = $db->query("SELECT date(created_at) as day, COUNT(*) as count
        FROM entries
        WHERE created_at >= date('now', '-30 days', 'localtime')
        GROUP BY date(created_at)
        ORDER BY day ASC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
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
