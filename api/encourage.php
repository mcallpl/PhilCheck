<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireLoginAPI();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $newContent = trim($input['content'] ?? '');
    if (empty($newContent)) {
        throw new Exception('No content provided');
    }

    $db = getDB();
    $entryCount = $db->query("SELECT COUNT(*) AS cnt FROM entries")->fetch_assoc()['cnt'];

    // Gather recent entries for context (last 10)
    $recentEntries = [];
    $result = $db->query("SELECT content, created_at FROM entries ORDER BY created_at DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $recentEntries[] = "({$row['created_at']}): {$row['content']}";
    }
    $context = implode("\n---\n", $recentEntries);

    if (empty(CLAUDE_API_KEY)) {
        echo json_encode(['success' => true, 'message' => "Great job adding that, Phil! You now have {$entryCount} entries. Keep going — every note helps me understand your health better!"]);
        exit;
    }

    $systemPrompt = "You are PhilCheck, Phil's warm and caring health companion. Phil just added a new health entry to his journal. " .
        "Your job is to respond with TWO things:\n\n" .
        "1. A MINI-INSIGHT: Something specific you can observe or connect from this new entry combined with his previous entries. " .
        "Make it feel like a real discovery — 'Oh interesting, Phil!' or 'You know what I'm noticing?' — something that makes him feel like this entry was really valuable.\n\n" .
        "2. A GENTLE NUDGE: Encourage him to add even more detail or another entry. Ask a specific follow-up question based on what he just shared. " .
        "For example, if he mentioned eating chicken, ask 'How did you feel an hour after that meal?' If he mentioned being tired, ask 'What time did you go to bed last night?'\n\n" .
        "RULES:\n" .
        "- Keep the TOTAL response to 3-4 sentences\n" .
        "- Be genuinely warm and excited about what he's sharing\n" .
        "- Make the insight feel like a real discovery, not generic praise\n" .
        "- The follow-up question should be specific to what he just shared\n" .
        "- Mention how many entries he has now ({$entryCount}) to show his library is growing\n" .
        "- Never be preachy or give medical advice\n\n";

    if (!empty($recentEntries)) {
        $systemPrompt .= "Phil's recent entries for context:\n{$context}\n\n";
    }

    $payload = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 300,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => "I just added this to my health journal:\n\n{$newContent}"]
        ]
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
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['success' => true, 'message' => "Awesome, Phil! Entry #{$entryCount} is saved. The more you share, the more patterns I can find for you. What else is on your mind?"]);
        exit;
    }

    $data = json_decode($response, true);
    $message = $data['content'][0]['text'] ?? "Entry saved! Keep adding more — I'm learning your patterns!";

    echo json_encode(['success' => true, 'message' => $message, 'entry_count' => $entryCount]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
