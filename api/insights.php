<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();

    // Only generate insights if we have enough entries
    $entryCount = $db->querySingle("SELECT COUNT(*) FROM entries");
    if ($entryCount < 3) {
        echo json_encode(['success' => true, 'insight' => null, 'reason' => 'Not enough entries yet']);
        exit;
    }

    // Check if we generated an insight recently (no more than once per 6 hours)
    $lastInsight = $db->querySingle("SELECT created_at FROM chat_history WHERE role='insight' ORDER BY id DESC LIMIT 1");
    if ($lastInsight) {
        $lastTime = strtotime($lastInsight);
        if (time() - $lastTime < 6 * 3600) {
            // Return the most recent insight instead
            $recent = $db->querySingle("SELECT message FROM chat_history WHERE role='insight' ORDER BY id DESC LIMIT 1");
            echo json_encode(['success' => true, 'insight' => $recent, 'cached' => true]);
            exit;
        }
    }

    // Gather all entries
    $entries = [];
    $totalChars = 0;
    $result = $db->query("SELECT content, source_name, created_at FROM entries ORDER BY created_at ASC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $entryText = "--- {$row['created_at']}" .
            ($row['source_name'] ? " ({$row['source_name']})" : "") .
            " ---\n{$row['content']}\n";
        if ($totalChars + strlen($entryText) > 80000) break;
        $entries[] = $entryText;
        $totalChars += strlen($entryText);
    }
    $healthContext = implode("\n", $entries);

    // Build insight prompt
    $systemPrompt = "You are PhilCheck's Insight Engine. Phil has been tracking his health — meals, symptoms, energy, moods, and daily conversations about his wellbeing.\n\n" .
        "Your job: analyze Phil's full health journal below and discover ONE genuinely surprising or mind-blowing insight that Phil probably hasn't noticed himself.\n\n" .
        "RULES:\n" .
        "- Find a NON-OBVIOUS pattern, correlation, or trend. Something that makes Phil go 'Wow, I never realized that!'\n" .
        "- Examples: 'You seem to feel best 2 days AFTER eating fish' or 'Your energy dips every Wednesday — could be related to your Tuesday routine' or 'Your mood has been steadily improving since you started mentioning walking'\n" .
        "- Be specific — reference actual dates, foods, symptoms from the entries\n" .
        "- Keep it warm, encouraging, and conversational — like a caring friend who noticed something\n" .
        "- 2-4 sentences max. Start with something attention-grabbing.\n" .
        "- End with an encouraging note about how this knowledge can help Phil\n" .
        "- Do NOT repeat insights that were already given (see previous insights below)\n" .
        "- You are not a doctor — don't diagnose, just observe patterns\n\n";

    // Include previous insights to avoid repeats
    $prevInsights = [];
    $insightResult = $db->query("SELECT message FROM chat_history WHERE role='insight' ORDER BY id DESC LIMIT 5");
    while ($row = $insightResult->fetchArray(SQLITE3_ASSOC)) {
        $prevInsights[] = $row['message'];
    }
    if (!empty($prevInsights)) {
        $systemPrompt .= "PREVIOUS INSIGHTS (do NOT repeat these):\n" . implode("\n---\n", $prevInsights) . "\n\n";
    }

    $systemPrompt .= "Phil's Health Journal ({$entryCount} entries):\n\n{$healthContext}";

    if (empty(CLAUDE_API_KEY)) {
        throw new Exception('API key not configured');
    }

    $payload = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 500,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => 'Analyze my health journal and tell me something surprising I probably haven\'t noticed.']
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
        throw new Exception("API error (HTTP {$httpCode})");
    }

    $data = json_decode($response, true);
    $insight = $data['content'][0]['text'] ?? null;

    if ($insight) {
        // Save insight
        $stmt = $db->prepare("INSERT INTO chat_history (role, message) VALUES ('insight', :msg)");
        $stmt->bindValue(':msg', $insight, SQLITE3_TEXT);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'insight' => $insight]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
