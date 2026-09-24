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

use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class AppApiCustomerServiceQueryService
{
    public function __construct(
        private readonly CustomerServiceConversationRepository $repository,
        private readonly CustomerServiceSettingsResolver $settings,
    ) {}

    public function config(): array
    {
        $config = $this->settings->toArray();
        return array_intersect_key($config, array_flip([
            'enabled', 'gateway_url', 'socket_path', 'allow_member_image', 'allow_product_card',
            'max_image_size_mb', 'max_message_length', 'welcome_message', 'offline_message',
        ]));
    }

    public function messages(int $memberId, string $conversationNo, ?int $beforeId, int $limit): array
    {
        $conversation = $this->repository->findMemberConversation($memberId, $conversationNo) ?? throw new \RuntimeException('客服会话不存在');
        return $this->repository->messages($conversation, $beforeId, max(1, min($limit, 100)));
    }
}
