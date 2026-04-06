<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireLoginAPI();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['message'])) {
        throw new Exception('No message provided');
    }

    $userMessage = trim($input['message']);
    $db = getDB();

    // Save user message to chat history
    $stmt = $db->prepare("INSERT INTO chat_history (role, message) VALUES ('user', :msg)");
    $stmt->bindValue(':msg', $userMessage, SQLITE3_TEXT);
    $stmt->execute();

    // Gather all health entries as context
    $entries = [];
    $totalChars = 0;
    $maxChars = 80000; // Keep context manageable

    $result = $db->query("SELECT content, source_name, created_at FROM entries ORDER BY created_at ASC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $entryText = "--- Entry from {$row['created_at']}" .
            ($row['source_name'] ? " (Source: {$row['source_name']})" : "") .
            " ---\n{$row['content']}\n";
        if ($totalChars + strlen($entryText) > $maxChars) break;
        $entries[] = $entryText;
        $totalChars += strlen($entryText);
    }

    $healthContext = implode("\n", $entries);
    $entryCount = count($entries);

    // Get recent chat history for continuity
    $recentChat = [];
    $chatResult = $db->query("SELECT role, message FROM chat_history ORDER BY id DESC LIMIT 10");
    while ($row = $chatResult->fetchArray(SQLITE3_ASSOC)) {
        $recentChat[] = $row;
    }
    $recentChat = array_reverse($recentChat);

    // Build the system prompt
    $systemPrompt = "You are PhilCheck, a warm and caring personal health assistant for Phil. " .
        "Phil has been tracking his daily health conversations, meals, symptoms, energy levels, and how he feels. " .
        "Your job is to help Phil understand his health patterns, give him practical advice, and be a supportive companion.\n\n" .
        "IMPORTANT GUIDELINES:\n" .
        "- Be warm, friendly, and encouraging — Phil is your friend\n" .
        "- When Phil asks about patterns or trends, carefully analyze ALL the health entries below\n" .
        "- Reference specific dates and details from the entries when answering\n" .
        "- If Phil asks about food, look through entries for mentions of meals, snacks, and how he felt after\n" .
        "- If Phil asks about energy or how he feels at different times, look for time-of-day patterns\n" .
        "- Give practical, actionable suggestions based on what you observe in HIS data\n" .
        "- You are NOT a doctor — remind Phil to check with his doctor for medical decisions\n" .
        "- If there isn't enough data yet, let Phil know kindly and encourage him to keep logging\n\n";

    if ($entryCount > 0) {
        $systemPrompt .= "Phil's Health Journal ({$entryCount} entries):\n\n{$healthContext}";
    } else {
        $systemPrompt .= "Phil hasn't added any health entries yet. Encourage him to start logging by dragging in his health notes or typing about how he's feeling today.";
    }

    // Build messages array
    $messages = [];
    // Include recent chat for continuity (skip the last one which is the current message)
    for ($i = 0; $i < count($recentChat) - 1; $i++) {
        $messages[] = [
            'role' => $recentChat[$i]['role'],
            'content' => $recentChat[$i]['message']
        ];
    }
    $messages[] = ['role' => 'user', 'content' => $userMessage];

    // Call Claude API
    if (empty(CLAUDE_API_KEY)) {
        throw new Exception('Claude API key not configured. Please add it to config.local.php');
    }

    $payload = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => CLAUDE_MAX_TOKENS,
        'system' => $systemPrompt,
        'messages' => $messages
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $errBody = json_decode($response, true);
        $errMsg = $errBody['error']['message'] ?? "API error (HTTP {$httpCode})";
        throw new Exception($errMsg);
    }

    $data = json_decode($response, true);
    $reply = $data['content'][0]['text'] ?? 'Sorry, I couldn\'t generate a response.';

    // Save assistant reply
    $stmt = $db->prepare("INSERT INTO chat_history (role, message) VALUES ('assistant', :msg)");
    $stmt->bindValue(':msg', $reply, SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(['success' => true, 'reply' => $reply, 'entries_count' => $entryCount]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
