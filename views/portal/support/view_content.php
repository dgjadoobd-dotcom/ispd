<?php
global $ticket, $replies, $aiSuggestion;
?>
<div class="max-w-4xl mx-auto">
    <?php if (env('AI_ENABLED') && $aiSuggestion): ?>
    <div class="mb-6 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/50 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shrink-0 mt-0.5">
                <i class="fas fa-robot text-sm"></i>
            </div>
            <div>
                <h4 class="font-semibold text-indigo-900 dark:text-indigo-100 mb-1">AI Troubleshooting Assistant</h4>
                <p class="text-sm text-indigo-800/80 dark:text-indigo-200/80 leading-relaxed italic">
                    <?= nl2br(sanitize($aiSuggestion)) ?>
                </p>
                <div class="mt-3 flex items-center gap-2 text-xs text-indigo-500 font-medium">
                    <span class="flex items-center gap-1"><i class="fas fa-info-circle"></i> AI-generated tips for faster resolution</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border">
        <div class="p-6 border-b flex justify-between items-start">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded"><?= sanitize($ticket['ticket_number']) ?></span>
                    <span class="px-2 py-1 text-xs rounded-full <?= $ticket['status'] === 'open' ? 'bg-red-100 text-red-700' : ($ticket['status'] === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') ?>">
                        <?= ucwords(str_replace('_', ' ', $ticket['status'])) ?>
                    </span>
                </div>
                <h3 class="text-xl font-semibold"><?= sanitize($ticket['subject']) ?></h3>
            </div>
            <?php if ($ticket['status'] !== 'closed'): ?>
            <form action="<?= base_url('portal/support/close') ?>" method="POST" onsubmit="return confirm('Close this ticket?')">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <button type="submit" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Close</button>
            </form>
            <?php endif; ?>
        </div>
        <div class="p-6 max-h-[400px] overflow-y-auto">
            <div class="space-y-4">
                <?php foreach ($replies as $r): ?>
                <div class="<?= ($r['customer_id'] ?? null) ? 'bg-blue-50' : 'bg-gray-50' ?> rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded-full <?= ($r['customer_id'] ?? null) ? 'bg-blue-500' : 'bg-gray-500' ?> flex items-center justify-center text-white font-semibold mr-2">
                            <?= strtoupper(substr(($r['customer_name'] ?? $r['staff_name'] ?? 'S')[0], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-medium"><?= sanitize($r['customer_name'] ?? $r['staff_name'] ?? 'Staff') ?></p>
                            <p class="text-xs text-gray-500"><?= formatDate($r['created_at'], 'd M Y h:i A') ?></p>
                        </div>
                    </div>
                    <p class="text-gray-700 whitespace-pre-wrap"><?= nl2br(sanitize($r['message'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="p-6 border-t">
            <form action="<?= base_url('portal/support/reply') ?>" method="POST">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <textarea name="message" rows="3" required class="w-full px-4 py-2 border rounded-lg mb-3" placeholder="Type your reply..."></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600"><i class="fas fa-reply mr-2"></i>Send</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <a href="<?= base_url('portal/support') ?>" class="inline-block mt-4 text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left mr-2"></i>Back</a>
</div>
