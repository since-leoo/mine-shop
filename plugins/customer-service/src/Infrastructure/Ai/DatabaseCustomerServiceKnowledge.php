<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Ai;

use App\Application\Api\Order\AppApiOrderQueryService;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Domain\Contract\CustomerServiceKnowledgeInterface;

final class DatabaseCustomerServiceKnowledge implements CustomerServiceKnowledgeInterface
{
    public function __construct(private readonly AppApiOrderQueryService $orders) {}

    public function search(string $question, array $agent, ?int $categoryId, int $memberId): array
    {
        $evidence = [];
        if (in_array('order_status', $agent['tool_allowlist'] ?? [], true) && preg_match('/订单号[：:\s]*([a-zA-Z0-9-]{8,64})/u', $question, $match)) {
            // Identity comes exclusively from the authenticated conversation, never from model output.
            $order = $this->orders->getOrderDetail($memberId, $match[1]);
            if ($order === null) { return []; }
            $evidence[] = ['id' => 'order:' . $match[1], 'text' => json_encode(['order_no' => $order->order_no, 'status' => $order->status, 'pay_status' => $order->pay_status, 'shipping_status' => $order->shipping_status], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
        }
        $rows = Db::table('customer_service_faqs as f')->join('customer_service_faq_revisions as r', 'f.published_revision_id', '=', 'r.id')->where('f.enabled', true)
            ->where(function ($q) { $q->whereNull('r.effective_at')->orWhere('r.effective_at', '<=', date('Y-m-d H:i:s')); })
            ->where(function ($q) { $q->whereNull('r.expires_at')->orWhere('r.expires_at', '>', date('Y-m-d H:i:s')); })->limit(1000)->get(['r.id', 'r.content_snapshot']);
        $ranked = [];
        foreach ($rows as $row) {
            $faq = json_decode($row->content_snapshot, true, 512, JSON_THROW_ON_ERROR);
            if (!empty($agent['category_ids']) && !in_array((int) ($faq['category_id'] ?? 0), array_map('intval', $agent['category_ids']), true)) { continue; }
            $questions = [$faq['question'], ...($faq['alternate_questions'] ?? [])];
            $score = 0.0;
            foreach ($questions as $candidate) { $score = max($score, $this->similarity($question, $candidate)); }
            if ($score >= (float) ($agent['retrieval_threshold'] ?? 0.35)) { $ranked[] = ['id' => 'faq:' . $row->id, 'text' => $faq['question'] . "\n" . $faq['answer'], 'score' => $score]; }
        }
        usort($ranked, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_merge($evidence, array_slice($ranked, 0, 3));
    }

    private function similarity(string $left, string $right): float
    {
        $normalize = static fn ($value) => mb_strtolower(preg_replace('/[\s\p{P}]+/u', '', $value) ?? $value);
        $left = $normalize($left); $right = $normalize($right);
        if ($left === $right && $left !== '') { return 1.0; }
        $grams = static function ($value) {
            $parts = [];
            for ($i = 0; $i < mb_strlen($value) - 1; ++$i) { $parts[] = mb_substr($value, $i, 2); }
            return array_unique($parts);
        };
        $a = $grams($left); $b = $grams($right);
        return count(array_intersect($a, $b)) / max(1, count(array_unique([...$a, ...$b])));
    }
}
