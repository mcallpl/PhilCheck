<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireAdminAPI();

try {
    $db = getDB();

    // Gather ALL entries for deep analysis
    $entries = [];
    $totalChars = 0;
    $result = $db->query("SELECT content, source_name, created_at FROM entries ORDER BY created_at ASC");
    while ($row = $result->fetch_assoc()) {
        $entryText = "--- {$row['created_at']}" .
            ($row['source_name'] ? " ({$row['source_name']})" : "") .
            " ---\n{$row['content']}\n";
        if ($totalChars + strlen($entryText) > 90000) break;
        $entries[] = $entryText;
        $totalChars += strlen($entryText);
    }
    $healthContext = implode("\n", $entries);
    $entryCount = count($entries);

    if ($entryCount < 1) {
        echo json_encode(['success' => true, 'profile' => null, 'reason' => 'No entries yet']);
        exit;
    }

    if (empty(CLAUDE_API_KEY)) {
        throw new Exception('API key not configured');
    }

    $systemPrompt = <<<PROMPT
You are an AI health analyst helping a caring son (Chip) understand his father Phil's health and wellbeing. Chip has set up a health journal for Phil and wants to monitor how his dad is doing.

Analyze ALL of Phil's journal entries below and produce a comprehensive profile in the following JSON format. Be specific — reference actual details from the entries. If there isn't enough data for a section, say so honestly.

Return ONLY valid JSON with these keys:

{
  "overall_mood": "A 1-2 sentence summary of Phil's general emotional state and outlook",
  "mood_trend": "improving" | "stable" | "declining" | "not_enough_data",
  "energy_level": "A description of Phil's typical energy patterns — when he's up, when he's down",
  "ailments": ["List each health complaint, symptom, or condition Phil has mentioned"],
  "ailment_details": "A paragraph describing Phil's health issues in more detail — what bothers him most, how often, any triggers",
  "fears_concerns": ["List any worries, fears, or anxieties Phil has expressed"],
  "interests": ["List things Phil seems interested in, enjoys, or talks positively about"],
  "food_patterns": {
    "foods_that_help": ["Foods or eating patterns that seem to correlate with Phil feeling good"],
    "foods_that_hurt": ["Foods or eating patterns that seem to correlate with Phil feeling bad"],
    "eating_habits": "Summary of Phil's general eating patterns"
  },
  "sleep_patterns": "What we know about Phil's sleep — quality, timing, issues",
  "social_connections": "Who does Phil mention? How does he seem socially?",
  "positive_signs": ["List encouraging things — improvements, good habits, positive moments"],
  "concerning_signs": ["List things that might warrant attention or a conversation"],
  "recommendations": ["3-5 specific, actionable suggestions for how Chip can help his dad based on the data"],
  "summary": "A warm, caring 3-4 sentence summary written TO Chip about how his dad is doing overall"
}
PROMPT;

    $systemPrompt .= "\n\nPhil's Health Journal ({$entryCount} entries):\n\n{$healthContext}";

    $payload = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 3000,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => 'Analyze Phil\'s complete health journal and generate the JSON profile.']
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
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API error (HTTP {$httpCode})");
    }

    $data = json_decode($response, true);
    $text = $data['content'][0]['text'] ?? '';

    // Extract JSON from response (Claude may wrap it in markdown)
    if (preg_match('/\{[\s\S]*\}/', $text, $match)) {
        $profile = json_decode($match[0], true);
    } else {
        $profile = null;
    }

    echo json_encode(['success' => true, 'profile' => $profile, 'entry_count' => $entryCount]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
