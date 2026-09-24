<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\CustomerService\Application\Api;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;
use Plugin\CustomerService\Service\CustomerServiceAssignmentService;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class AppApiCustomerServiceCommandService
{
    public function __construct(
        private readonly CustomerServiceConversationRepository $repository,
        private readonly CustomerServiceSettingsResolver $settings,
        private readonly ProductSnapshotInterface $products,
        private readonly CustomerServiceAssignmentService $assignment,
    ) {}

    public function open(int $memberId, string $source, ?string $subject): CustomerServiceConversation
    {
        return Db::transaction(function () use ($memberId, $source, $subject) {
            $conversation = $this->repository->findOpenByMember($memberId) ?? $this->repository->createConversation($memberId, $source, $subject);
            if ($conversation->status === 'waiting') {
                $this->assignment->assignWaitingConversation($conversation);
            }
            return $conversation->refresh();
        });
    }

    public function sendText(int $memberId, string $conversationNo, string $clientMessageId, string $text): array
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > (int) $this->settings->toArray()['max_message_length']) {
            throw new \InvalidArgumentException('消息内容长度无效');
        }
        return $this->appendMemberMessage($memberId, $conversationNo, $clientMessageId, 'text', ['text' => $text]);
    }

    public function sendImage(int $memberId, string $conversationNo, string $clientMessageId, string $url): array
    {
        if (! $this->settings->toArray()['allow_member_image'] || ! filter_var($url, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('图片地址无效或当前未开启图片消息');
        }
        return $this->appendMemberMessage($memberId, $conversationNo, $clientMessageId, 'image', ['url' => $url]);
    }

    public function sendProductCard(int $memberId, string $conversationNo, string $clientMessageId, int $productId, ?int $skuId = null): array
    {
        if (! $this->settings->toArray()['allow_product_card'] || $productId <= 0) {
            throw new \InvalidArgumentException('商品卡片不可用');
        }
        $product = $this->products->getProduct($productId, ['skus']);
        if ($product === null) {
            throw new \RuntimeException('商品不存在');
        }
        return $this->appendMemberMessage($memberId, $conversationNo, $clientMessageId, 'product', [
            'product_id' => $productId, 'sku_id' => $skuId, 'title' => (string) ($product['name'] ?? ''),
            'image' => (string) ($product['main_image'] ?? ''), 'price' => (int) ($product['min_price'] ?? 0),
            'url' => '/pages/goods/details/index?id=' . $productId, 'snapshot_version' => (string) ($product['updated_at'] ?? date(\DATE_ATOM)),
        ]);
    }

    public function sendFaq(int $memberId, string $conversationNo, string $clientMessageId, int $faqId): array
    {
        $faq = CustomerServiceFaq::query()->where('id', $faqId)->where('enabled', true)->first();
        if ($faq === null) {
            throw new \RuntimeException('常见问题不存在或已下架');
        }
        return $this->appendMemberMessage($memberId, $conversationNo, $clientMessageId, 'faq', [
            'faq_id' => $faq->id, 'question' => $faq->question, 'answer' => $faq->answer,
        ]);
    }

    public function close(int $memberId, string $conversationNo): void
    {
        $conversation = $this->memberConversation($memberId, $conversationNo);
        if (! \in_array($conversation->status, ['waiting', 'assigned', 'active'], true)) {
            throw new \DomainException('当前会话不能关闭');
        }
        Db::transaction(function () use ($conversation, $memberId) {
            $conversation->update(['status' => 'closed', 'closed_by' => $memberId, 'closed_reason' => 'member_closed', 'closed_at' => now()]);
            $this->assignment->release($conversation);
            $this->assignment->log($conversation, 'closed', 'member', $memberId, ['reason' => 'member_closed']);
        });
    }

    private function memberConversation(int $memberId, string $conversationNo): CustomerServiceConversation
    {
        return $this->repository->findMemberConversation($memberId, $conversationNo) ?? throw new \RuntimeException('客服会话不存在');
    }

    private function appendMemberMessage(int $memberId, string $conversationNo, string $clientMessageId, string $type, array $content): array
    {
        if ($clientMessageId === '' || mb_strlen($clientMessageId) > 64) {
            throw new \InvalidArgumentException('客户端消息标识无效');
        }
        $conversation = $this->memberConversation($memberId, $conversationNo);
        if (! \in_array($conversation->status, ['waiting', 'assigned', 'active'], true)) {
            throw new \DomainException('会话已关闭');
        }
        return $this->repository->appendMessage($conversation, $memberId, $clientMessageId, $type, $content)->toArray();
    }
}
