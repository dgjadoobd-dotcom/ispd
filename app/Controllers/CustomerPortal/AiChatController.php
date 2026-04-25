<?php
/**
 * AiChatController — AI assistant for the Customer Portal.
 *
 * Uses LM Studio (local) or any OpenAI-compatible API.
 * Falls back to a rule-based engine if AI is disabled or unreachable.
 */
class AiChatController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── POST /portal/ai/chat ──────────────────────────────────────
    public function chat(): void
    {
        // Must be logged-in portal customer
        $customerId = $_SESSION['portal_customer_id'] ?? null;
        if (!$customerId) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim(strip_tags($body['message'] ?? ''));
        if (empty($message)) {
            jsonResponse(['error' => 'Empty message'], 400);
        }

        // Load customer context
        $customer = $this->db->fetchOne(
            "SELECT c.*, p.name as package_name, p.speed_download, p.speed_upload, p.price as package_price
             FROM customers c LEFT JOIN packages p ON p.id = c.package_id
             WHERE c.id = ?",
            [$customerId]
        );
        if (!$customer) {
            jsonResponse(['error' => 'Customer not found'], 404);
        }

        // Try rule-based first for instant common queries
        $ruleReply = $this->ruleBasedReply($message, $customer);
        if ($ruleReply !== null) {
            jsonResponse(['reply' => $ruleReply, 'source' => 'rules']);
        }

        // Try AI
        $aiEnabled = !empty($_ENV['AI_ENABLED']) && $_ENV['AI_ENABLED'] !== '0' && $_ENV['AI_ENABLED'] !== 'false';
        if ($aiEnabled) {
            $aiReply = $this->callAi($message, $customer);
            if ($aiReply !== null) {
                jsonResponse(['reply' => $aiReply, 'source' => 'ai']);
            }
        }

        // Final fallback
        jsonResponse([
            'reply'  => $this->fallbackReply($message, $customer),
            'source' => 'fallback',
        ]);
    }

    // ── Rule-based instant replies ────────────────────────────────
    private function ruleBasedReply(string $msg, array $c): ?string
    {
        $m = strtolower($msg);

        // Due / bill
        if (preg_match('/\b(due|bill|pay|payment|invoice|amount|balance)\b/', $m)) {
            $due = (float)($c['due_amount'] ?? 0);
            $adv = (float)($c['advance_balance'] ?? 0);
            if ($due > 0) {
                return "Your current due amount is **৳" . number_format($due, 2) . "**. "
                     . "You can pay via bKash, Nagad, or bank transfer from the **Pay Bill** button above. "
                     . ($adv > 0 ? "You also have ৳" . number_format($adv, 2) . " advance balance." : "");
            }
            return "Great news! You have **no outstanding dues** right now. "
                 . ($adv > 0 ? "Your advance balance is ৳" . number_format($adv, 2) . "." : "");
        }

        // Package / speed
        if (preg_match('/\b(package|speed|bandwidth|plan|mbps|download|upload)\b/', $m)) {
            return "You are on the **{$c['package_name']}** package — "
                 . "⬇ {$c['speed_download']} download / ⬆ {$c['speed_upload']} upload. "
                 . "Monthly charge: ৳" . number_format((float)($c['package_price'] ?? $c['monthly_charge'] ?? 0), 2) . ". "
                 . "To upgrade, please contact support.";
        }

        // Connection status
        if (preg_match('/\b(status|connect|online|offline|active|suspend)\b/', $m)) {
            $status = ucfirst($c['status'] ?? 'unknown');
            $color  = $c['status'] === 'active' ? '🟢' : '🔴';
            return "$color Your connection status is **$status**. "
                 . ($c['status'] !== 'active'
                    ? "If you believe this is an error, please create a support ticket."
                    : "Your PPPoE username is `{$c['pppoe_username']}`.");
        }

        // Password
        if (preg_match('/\b(password|pppoe|username|login|credential)\b/', $m)) {
            return "Your PPPoE username is **`{$c['pppoe_username']}`**. "
                 . "For security reasons, passwords are not displayed here. "
                 . "You can reset your portal password from **Profile → Change Password**.";
        }

        // Support / ticket
        if (preg_match('/\b(support|ticket|help|problem|issue|complaint|report)\b/', $m)) {
            return "I can help you raise a support ticket! Go to **Support → New Ticket** or click here: "
                 . "[Create Ticket](portal/support/create). "
                 . "Our team typically responds within 2–4 hours.";
        }

        // Greeting
        if (preg_match('/^(hi|hello|hey|salam|assalam|namaskar|good\s*(morning|afternoon|evening))/i', $m)) {
            $name = explode(' ', $c['full_name'])[0];
            return "Hello **$name**! 👋 I'm your ISP assistant. I can help you with:\n"
                 . "• Bill & payment info\n"
                 . "• Connection status\n"
                 . "• Package details\n"
                 . "• Support tickets\n\n"
                 . "What can I help you with today?";
        }

        // Thanks
        if (preg_match('/\b(thank|thanks|dhonnobad|shukriya)\b/i', $m)) {
            return "You're welcome! 😊 Is there anything else I can help you with?";
        }

        return null; // no rule matched — try AI
    }

    // ── AI call via AiService (supports Ollama + OpenAI-compat) ──
    private function callAi(string $message, array $c): ?string
    {
        require_once BASE_PATH . '/app/Services/AiService.php';
        $ai = new AiService();

        $systemPrompt = "You are a helpful ISP customer support assistant for Digital ISP. "
            . "You are talking to customer: {$c['full_name']} (ID: {$c['customer_code']}). "
            . "Their package: {$c['package_name']}, speed: {$c['speed_download']}/{$c['speed_upload']}, "
            . "status: {$c['status']}, due amount: ৳{$c['due_amount']}. "
            . "Answer concisely in the same language the customer uses (Bangla or English). "
            . "For billing/payment issues, guide them to use the Pay Bill button. "
            . "For technical issues, suggest creating a support ticket. "
            . "Never reveal passwords. Keep responses under 150 words.";

        return $ai->getChatCompletion([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $message],
        ], ['max_tokens' => 200, 'temperature' => 0.7]);
    }

    // ── Generic fallback ──────────────────────────────────────────
    private function fallbackReply(string $msg, array $c): string
    {
        return "I'm not sure about that, but I can help you with:\n"
             . "• **Bill info** — ask \"what is my due amount?\"\n"
             . "• **Package** — ask \"what is my package?\"\n"
             . "• **Connection** — ask \"what is my connection status?\"\n"
             . "• **Support** — ask \"I need help\" to create a ticket\n\n"
             . "Or call us directly for immediate assistance.";
    }
}
