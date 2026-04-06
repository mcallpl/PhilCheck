<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireLoginAPI();

try {
    $db = getDB();

    // Handle pasted text
    if (isset($_POST['text_content']) && trim($_POST['text_content']) !== '') {
        $content = trim($_POST['text_content']);
        $stmt = $db->prepare("INSERT INTO entries (content, source_type, source_name) VALUES (:content, 'paste', 'Pasted Text')");
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Text saved successfully', 'id' => $db->lastInsertRowID()]);
        exit;
    }

    // Handle file uploads
    if (empty($_FILES['files'])) {
        throw new Exception('No files uploaded');
    }

    $results = [];
    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $count; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];

        if ($error !== UPLOAD_ERR_OK) {
            $results[] = ['file' => $name, 'success' => false, 'error' => 'Upload error'];
            continue;
        }

        if ($size > MAX_UPLOAD_SIZE) {
            $results[] = ['file' => $name, 'success' => false, 'error' => 'File too large'];
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $content = '';

        if ($ext === 'pdf') {
            $content = extractPDFText($tmpName);
            if (empty(trim($content))) {
                $results[] = ['file' => $name, 'success' => false, 'error' => 'Could not extract text from PDF. Try copying the text and pasting it instead.'];
                continue;
            }
            $sourceType = 'pdf';
        } elseif (in_array($ext, ['txt', 'text', 'md', 'csv', 'log', 'rtf'])) {
            $content = file_get_contents($tmpName);
            $sourceType = 'text_file';
        } else {
            // Try to read as text anyway
            $content = file_get_contents($tmpName);
            if (mb_check_encoding($content, 'UTF-8') && !preg_match('/[\x00-\x08\x0E-\x1F]/', $content)) {
                $sourceType = 'text_file';
            } else {
                $results[] = ['file' => $name, 'success' => false, 'error' => 'Unsupported file type'];
                continue;
            }
        }

        $content = trim($content);
        if (empty($content)) {
            $results[] = ['file' => $name, 'success' => false, 'error' => 'File appears to be empty'];
            continue;
        }

        $stmt = $db->prepare("INSERT INTO entries (content, source_type, source_name) VALUES (:content, :type, :name)");
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->bindValue(':type', $sourceType, SQLITE3_TEXT);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->execute();

        $results[] = ['file' => $name, 'success' => true, 'id' => $db->lastInsertRowID(), 'preview' => mb_substr($content, 0, 200)];
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function extractPDFText($filepath) {
    // Try pdftotext command first (most reliable)
    $output = '';
    $returnCode = -1;
    $escaped = escapeshellarg($filepath);
    exec("pdftotext {$escaped} - 2>/dev/null", $outputLines, $returnCode);
    if ($returnCode === 0 && !empty($outputLines)) {
        return implode("\n", $outputLines);
    }

    // Fallback: basic PHP PDF text extraction
    $content = file_get_contents($filepath);
    $text = '';

    // Extract text between stream/endstream
    if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
        foreach ($matches[1] as $stream) {
            // Try to decompress
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            if ($decoded === false) {
                $decoded = $stream;
            }
            // Extract text operators
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $decoded, $tjMatches)) {
                foreach ($tjMatches[1] as $tj) {
                    if (preg_match_all('/\((.*?)\)/s', $tj, $strMatches)) {
                        $text .= implode('', $strMatches[1]);
                    }
                }
                $text .= "\n";
            }
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $decoded, $tjMatches)) {
                $text .= implode("\n", $tjMatches[1]) . "\n";
            }
        }
    }

    return $text;
}
