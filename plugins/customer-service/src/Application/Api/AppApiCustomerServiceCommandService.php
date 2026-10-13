<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application\Api;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Application\CustomerServiceOutbox;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Domain\Service\DomainConversationService;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class AppApiCustomerServiceCommandService
{
    public function __construct(private readonly CustomerServiceConversationRepository $repository, private readonly CustomerServiceSettingsResolver $settings, private readonly ProductSnapshotInterface $products, private readonly DomainConversationService $domain, private readonly CustomerServiceOutbox $outbox, private readonly \Plugin\CustomerService\Domain\Contract\CustomerServiceAttachmentInterface $attachments, private readonly \Plugin\CustomerService\Domain\Service\DomainFaqService $faqs) {}

    public function open(int $memberId, string $source, ?string $subject, int $protocolVersion = 1, ?int $categoryId = null): CustomerServiceConversation
    {
        return Db::transaction(function () use ($memberId, $source, $subject, $protocolVersion, $categoryId) {
            $conversation = $this->domain->open($memberId, $source, $subject, $protocolVersion, $categoryId);
            $this->outbox->conversationChanged($conversation);
            return $conversation;
        });
    }

    public function sendText(int $memberId, string $conversationNo, string $clientMessageId, string $text): array
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > (int) $this->settings->toArray()['max_message_length']) { throw new \InvalidArgumentException('消息内容长度无效'); }
        return $this->append($memberId, $conversationNo, $clientMessageId, 'text', ['text' => $text]);
    }

    public function sendImage(int $memberId, string $conversationNo, string $clientMessageId, string $url): array
    {
        if (!$this->settings->toArray()['allow_member_image'] || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) { throw new \InvalidArgumentException('图片地址无效或图片消息已关闭'); }
        $this->attachments->assertMemberImage($memberId, $url, (int) $this->settings->toArray()['max_image_size_mb'] * 1024 * 1024);
        return $this->append($memberId, $conversationNo, $clientMessageId, 'image', ['url' => $url]);
    }

    public function sendProductCard(int $memberId, string $conversationNo, string $clientMessageId, int $productId, ?int $skuId = null): array
    {
        if (!$this->settings->toArray()['allow_product_card'] || $productId <= 0) { throw new \InvalidArgumentException('商品卡片不可用'); }
        $product = $this->products->getProduct($productId, ['skus']) ?? throw new \RuntimeException('商品不存在');
        return $this->append($memberId, $conversationNo, $clientMessageId, 'product', ['product_id' => $productId, 'sku_id' => $skuId, 'title' => (string) ($product['name'] ?? ''), 'image' => (string) ($product['main_image'] ?? ''), 'price' => (int) ($product['min_price'] ?? 0), 'url' => '/pages/goods/details/index?id=' . $productId, 'snapshot_version' => (string) ($product['updated_at'] ?? date(DATE_ATOM))]);
    }

    public function sendFaq(int $memberId, string $conversationNo, string $clientMessageId, int $faqId): array
    {
        $faq = $this->faqs->published($faqId);
        return $this->append($memberId, $conversationNo, $clientMessageId, 'faq', ['faq_id' => $faq['id'], 'question' => $faq['question'], 'answer' => $faq['answer']]);
    }

    public function close(int $memberId, string $conversationNo): void
    {
        Db::transaction(function () use ($memberId, $conversationNo) { $this->outbox->conversationChanged($this->domain->closeMember($memberId, $conversationNo), 'conversation:closed'); });
    }

    public function requestHuman(int $memberId, string $conversationNo, string $reason = 'requested'): array
    {
        return Db::transaction(function () use ($memberId, $conversationNo, $reason) {
            $conversation = $this->repository->findMemberConversation($memberId, $conversationNo, true) ?? throw new \RuntimeException('会话不存在');
            $this->domain->requestHuman($conversation, $reason);
            $this->outbox->conversationChanged($conversation);
            return $conversation->toArray();
        });
    }

    public function classify(int $memberId, string $conversationNo, int $categoryId): array
    {
        return Db::transaction(function () use ($memberId, $conversationNo, $categoryId) {
            $conversation = $this->repository->findMemberConversation($memberId, $conversationNo, true) ?? throw new \RuntimeException('会话不存在');
            $this->domain->classify($conversation, $categoryId);
            $this->outbox->conversationChanged($conversation);
            return $conversation->toArray();
        });
    }

    public function rating(int $memberId, string $no, int $score, string $comment = ''): void
    {
        Db::transaction(fn () => $this->domain->rate($memberId, $no, $score, $comment));
    }

    private function append(int $memberId, string $no, string $clientId, string $type, array $content): array
    {
        return Db::transaction(function () use ($memberId, $no, $clientId, $type, $content) {
            $message = $this->domain->appendMember($memberId, $no, $clientId, $type, $content);
            if ($message->wasRecentlyCreated) {
                $conversation = $this->repository->findMemberConversation($memberId, $no);
                $this->outbox->messageCreated($conversation, $message->toArray());
                $this->outbox->conversationChanged($conversation);
            }
            return $message->toArray();
        });
    }
}
