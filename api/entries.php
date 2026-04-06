<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        if (!empty($search)) {
            $stmt = $db->prepare("SELECT e.id, e.content, e.source_type, e.source_name, e.created_at
                FROM entries e
                JOIN entries_fts ON entries_fts.rowid = e.id
                WHERE entries_fts MATCH :search
                ORDER BY e.created_at DESC
                LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':search', $search, SQLITE3_TEXT);
        } else {
            $stmt = $db->prepare("SELECT id, content, source_type, source_name, created_at
                FROM entries ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        }
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

        $entries = [];
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['preview'] = mb_substr($row['content'], 0, 300);
            $entries[] = $row;
        }

        $total = $db->querySingle("SELECT COUNT(*) FROM entries");

        echo json_encode(['success' => true, 'entries' => $entries, 'total' => $total, 'page' => $page]);

    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');

        $stmt = $db->prepare("DELETE FROM entries WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['success' => true]);

    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
