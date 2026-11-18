<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

use App\Infrastructure\Error\CustomerServiceError;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;

final class DomainFaqService
{
    public function __construct(private readonly SupportConfigurationInterface $configuration, private readonly DomainCategoryService $categories) {}

    public function save(?int $id, array $payload): CustomerServiceFaq
    {
        $question = trim((string) ($payload['question'] ?? ''));
        $answer = trim((string) ($payload['answer'] ?? ''));
        if ($question === '' || $answer === '' || mb_strlen($question) > 255 || mb_strlen($answer) > 20000) { throw CustomerServiceError::FAQ_CONTENT_LENGTH_INVALID->invalid(); }
        $categoryId = $payload['category_id'] ?? null;
        if ($categoryId !== null && (!is_int($categoryId) || $categoryId < 1 || !$this->categories->effective($this->configuration->published()['snapshot']['categories'] ?? [], $categoryId))) { throw CustomerServiceError::FAQ_CATEGORY_UNAVAILABLE->exception(); }
        $alternates = $payload['alternate_questions'] ?? [];
        if (!is_array($alternates) || count($alternates) > 50) { throw CustomerServiceError::FAQ_ALTERNATES_LIMIT_EXCEEDED->invalid(); }
        foreach ($alternates as $alternate) { if (!is_string($alternate) || trim($alternate) === '' || mb_strlen($alternate) > 255) { throw CustomerServiceError::FAQ_ALTERNATE_LENGTH_INVALID->invalid(); } }
        foreach (['enabled', 'publish'] as $key) { if (isset($payload[$key]) && !is_bool($payload[$key])) { throw CustomerServiceError::BOOLEAN_REQUIRED->invalid([$key]); } }
        foreach (['effective_at', 'expires_at'] as $key) { if (!empty($payload[$key]) && (!is_string($payload[$key]) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $payload[$key]) || strtotime($payload[$key]) === false)) { throw CustomerServiceError::FAQ_EFFECTIVE_TIME_INVALID->invalid(); } }
        if (!empty($payload['effective_at']) && !empty($payload['expires_at']) && $payload['expires_at'] <= $payload['effective_at']) { throw CustomerServiceError::FAQ_EXPIRATION_INVALID->exception(); }
        $faq = $id ? CustomerServiceFaq::query()->whereKey($id)->lockForUpdate()->first() ?? throw CustomerServiceError::FAQ_NOT_FOUND->internal() : new CustomerServiceFaq();
        $faq->fill(['question' => $question, 'answer' => $answer, 'category_name' => trim((string) ($payload['category_name'] ?? '')) ?: null, 'category_id' => $categoryId, 'alternate_questions' => array_values($alternates), 'sort' => max(0, (int) ($payload['sort'] ?? 0)), 'enabled' => $payload['enabled'] ?? true]);
        $faq->save();
        if ($payload['publish'] ?? false) {
            $version = (int) Db::table('customer_service_faq_revisions')->where('faq_id', $faq->id)->max('version') + 1;
            $revision = Db::table('customer_service_faq_revisions')->insertGetId(['faq_id' => $faq->id, 'version' => $version, 'content_snapshot' => json_encode($faq->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'effective_at' => ($payload['effective_at'] ?? null) ?: null, 'expires_at' => ($payload['expires_at'] ?? null) ?: null, 'created_at' => date('Y-m-d H:i:s')]);
            $faq->update(['published_revision_id' => $revision]);
        }
        return $faq;
    }

    public function delete(int $id): void
    {
        $faq = CustomerServiceFaq::query()->whereKey($id)->lockForUpdate()->first() ?? throw CustomerServiceError::FAQ_NOT_FOUND->internal();
        if ($faq->published_revision_id) { $faq->update(['enabled' => false]); } else { $faq->delete(); }
    }

    public function published(int $id): array
    {
        $faq = CustomerServiceFaq::query()->whereKey($id)->where('enabled', true)->first() ?? throw CustomerServiceError::FAQ_UNAVAILABLE->internal();
        $now = date('Y-m-d H:i:s');
        $revision = Db::table('customer_service_faq_revisions')->where('id', $faq->published_revision_id)
            ->where(static fn ($q) => $q->whereNull('effective_at')->orWhere('effective_at', '<=', $now))
            ->where(static fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now))->first();
        if (!$revision) { throw CustomerServiceError::FAQ_REVISION_UNAVAILABLE->exception(); }
        return json_decode($revision->content_snapshot, true, 512, JSON_THROW_ON_ERROR);
    }
}
