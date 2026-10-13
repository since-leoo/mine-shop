<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application\Api;

use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Domain\Service\DomainCategoryService;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class AppApiCustomerServiceQueryService
{
    public function __construct(private readonly CustomerServiceConversationRepository $repository, private readonly CustomerServiceSettingsResolver $settings, private readonly SupportConfigurationInterface $configuration, private readonly DomainCategoryService $categories) {}

    public function config(): array
    {
        return array_intersect_key($this->settings->toArray(), array_flip(['enabled', 'ai_enabled', 'gateway_url', 'socket_path', 'allow_member_image', 'allow_product_card', 'max_image_size_mb', 'max_message_length', 'history_page_size', 'welcome_message', 'offline_message']));
    }

    public function categories(): array
    {
        $categories = $this->configuration->published()['snapshot']['categories'] ?? [];
        $result = [];
        foreach ($categories as $category) {
            if ($this->categories->effective($categories, (int) $category['id'])) { $result[] = array_intersect_key($category, array_flip(['id', 'parent_id', 'name', 'depth', 'sort'])); }
        }
        return $result;
    }

    public function conversation(int $memberId, string $no): array
    {
        return ($this->repository->findMemberConversation($memberId, $no) ?? throw new \RuntimeException('客服会话不存在'))->toArray();
    }

    public function messages(int $memberId, string $conversationNo, ?int $beforeId, int $limit, ?int $afterSequence = null): array
    {
        $conversation = $this->repository->findMemberConversation($memberId, $conversationNo) ?? throw new \RuntimeException('客服会话不存在');
        return $this->repository->messages($conversation, $beforeId, max(1, min($limit, 100)), $afterSequence);
    }
}
