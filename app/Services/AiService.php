<?php

class AiService {
    private bool $enabled;
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public function __construct() {
        $this->enabled = (bool)env('AI_ENABLED', false);
        $this->baseUrl = env('AI_BASE_URL', 'http://localhost:1234/v1');
        $this->model   = env('AI_MODEL', 'google/gemma-4-e4b');
        $this->timeout = (int)env('AI_TIMEOUT', 30);
    }

    /**
     * Get a completion from the AI model
     */
    public function getChatCompletion(array $messages, array $options = []): ?string {
        if (!$this->enabled) return null;

        $url = rtrim($this->baseUrl, '/') . '/chat/completions';
        
        $data = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ], $options);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("AiService Error: HTTP $httpCode - $error - Response: $response");
            return null;
        }

        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Suggest a response for a support ticket
     */
    public function suggestSupportResponse(string $subject, string $description, array $history = []): ?string {
        $messages = [
            ['role' => 'system', 'content' => "You are a helpful ISP support assistant for 'Digital ISP ERP'. 
            Your goal is to provide polite, professional, and technical support responses to customer queries in Bangladesh.
            Keep responses concise and helpful. If it's a technical issue, suggest basic troubleshooting steps like restarting the router."],
            ['role' => 'user', 'content' => "Subject: $subject\nDescription: $description"]
        ];

        foreach ($history as $msg) {
            $role = ($msg['customer_id'] ?? null) ? 'user' : 'assistant';
            $messages[] = [
                'role' => $role,
                'content' => $msg['message']
            ];
        }

        return $this->getChatCompletion($messages);
    }

    /**
     * Analyze a support ticket and suggest a category/priority
     */
    public function analyzeTicket(string $subject, string $description): ?array {
        $prompt = "Analyze the following ISP support ticket and return a JSON object with 'category' and 'priority' fields.
        Categories: billing, technical, complaint, general, new_connection, disconnection.
        Priorities: low, normal, high, urgent.
        
        Subject: $subject
        Description: $description
        
        Return ONLY the JSON object.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a ticket classifier. Reply only with valid JSON.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->getChatCompletion($messages, ['temperature' => 0.1]);
        if (!$response) return null;

        if (preg_match('/\{.*\}/s', $response, $matches)) {
            return json_decode($matches[0], true);
        }

        return null;
    }
}
