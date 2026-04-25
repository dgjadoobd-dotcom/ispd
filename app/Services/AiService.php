<?php

class AiService {
    private bool   $enabled;
    private string $baseUrl;
    private string $model;
    private int    $timeout;
    private string $apiType; // 'ollama' | 'openai'

    public function __construct() {
        $this->enabled = (bool)env('AI_ENABLED', false);
        $this->baseUrl = rtrim(env('AI_BASE_URL', 'http://localhost:11434/api'), '/');
        $this->model   = env('AI_MODEL', 'gemma4:latest');
        $this->timeout = (int)env('AI_TIMEOUT', 60);

        // Auto-detect API type from base URL
        // Ollama native: .../api   |  OpenAI-compat: .../v1
        $this->apiType = str_ends_with($this->baseUrl, '/api') ? 'ollama' : 'openai';
    }

    public function getBaseUrl(): string {
        return $this->baseUrl;
    }
    
    public function getModel(): string {
        return $this->model;
    }
    
    public function getApiType(): string {
        return $this->apiType;
    }

    /**
     * Test connectivity to the AI backend.
     * Returns ['ok' => bool, 'models' => [...], 'error' => string]
     */
    public function testConnection(): array {
        if (!$this->enabled) {
            return ['ok' => false, 'error' => 'AI is disabled (AI_ENABLED=0)'];
        }

        if ($this->apiType === 'ollama') {
            $url = $this->baseUrl . '/tags'; // GET /api/tags → list models
        } else {
            $url = $this->baseUrl . '/models';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return ['ok' => false, 'error' => "HTTP $httpCode — $error"];
        }

        $data   = json_decode($response, true);
        $models = [];

        if ($this->apiType === 'ollama') {
            foreach (($data['models'] ?? []) as $m) {
                $models[] = $m['name'] ?? $m['model'] ?? '';
            }
        } else {
            foreach (($data['data'] ?? []) as $m) {
                $models[] = $m['id'] ?? '';
            }
        }

        return ['ok' => true, 'models' => $models, 'api_type' => $this->apiType];
    }

    /**
     * Get a chat completion — works with both Ollama native and OpenAI-compatible APIs.
     */
    public function getChatCompletion(array $messages, array $options = []): ?string {
        if (!$this->enabled) return null;

        if ($this->apiType === 'ollama') {
            return $this->_ollamaChat($messages, $options);
        }
        return $this->_openaiChat($messages, $options);
    }

    // ── Ollama /api/chat  (native messages format) ────────────
    private function _ollamaChat(array $messages, array $options = []): ?string {
        $url  = $this->baseUrl . '/chat';
        $data = [
            'model'    => $this->model,
            'messages' => $messages,   // [{role, content}, ...]  — same as OpenAI
            'stream'   => false,
            'options'  => [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens']  ?? 500,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("AiService Ollama Error: HTTP $httpCode — $error — $response");
            return null;
        }

        $result = json_decode($response, true);
        // /api/chat returns {"message": {"role": "assistant", "content": "..."}}
        return $result['message']['content'] ?? null;
    }

    // ── OpenAI-compatible /v1/chat/completions ────────────────
    private function _openaiChat(array $messages, array $options = []): ?string {
        $url  = $this->baseUrl . '/chat/completions';
        $data = array_merge([
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.7,
            'max_tokens'  => 500,
        ], $options);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("AiService OpenAI Error: HTTP $httpCode — $error — $response");
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
