<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireLoginAPI();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        if (!empty($search)) {
            $stmt = $db->prepare("SELECT id, content, source_type, source_name, created_at
                FROM entries
                WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?");
            $stmt->bind_param('sii', $search, $limit, $offset);
        } else {
            $stmt = $db->prepare("SELECT id, content, source_type, source_name, created_at
                FROM entries ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $row['preview'] = mb_substr($row['content'], 0, 300);
            $entries[] = $row;
        }
        $stmt->close();

        $totalResult = $db->query("SELECT COUNT(*) AS cnt FROM entries");
        $total = $totalResult->fetch_assoc()['cnt'];

        echo json_encode(['success' => true, 'entries' => $entries, 'total' => $total, 'page' => $page]);

    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');

        $stmt = $db->prepare("DELETE FROM entries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);

    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
