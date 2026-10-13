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

namespace Plugin\CustomerService\Application\Socket;

use Plugin\CustomerService\Domain\Contract\CustomerServiceTicketStoreInterface;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

/** 将 HTTP 已认证身份换成短期一次性票据，避免在 WebSocket URL 中暴露登录令牌。 */
final class CustomerServiceSocketTicketService
{
    public function __construct(private readonly CustomerServiceTicketStoreInterface $store, private readonly CustomerServiceSettingsResolver $settings) {}

    public function issueMemberTicket(int $memberId): array
    {
        return $this->issue(['principal_type' => 'member', 'principal_id' => $memberId]);
    }

    public function issueAgentTicket(int $agentId, int $adminUserId): array
    {
        return $this->issue(['principal_type' => 'agent', 'principal_id' => $agentId, 'admin_user_id' => $adminUserId]);
    }

    /** @return null|array{principal_type:string, principal_id:int, admin_user_id?:int} */
    public function consume(string $ticket): ?array
    {
        if ($ticket === '' || mb_strlen($ticket) !== 48) {
            return null;
        }
        $decoded = $this->store->consume($ticket);
        if (! \is_array($decoded) || ! in_array($decoded['principal_type'] ?? null, ['member', 'agent'], true) || (int) ($decoded['principal_id'] ?? 0) <= 0) {
            return null;
        }
        if ($decoded['principal_type'] === 'agent' && (int) ($decoded['admin_user_id'] ?? 0) <= 0) {
            return null;
        }
        return array_filter([
            'principal_type' => (string) $decoded['principal_type'],
            'principal_id' => (int) $decoded['principal_id'],
            'admin_user_id' => isset($decoded['admin_user_id']) ? (int) $decoded['admin_user_id'] : null,
        ], static fn ($value) => $value !== null);
    }

    private function issue(array $principal): array
    {
        if (! ($this->settings->toArray()['enabled'] ?? false)) {
            throw new \DomainException('客服服务暂未开启');
        }
        if ((int) $principal['principal_id'] <= 0) {
            throw new \InvalidArgumentException('无效的客服身份');
        }
        $ticket = bin2hex(random_bytes(24));
        $expiresIn = 60;
        $this->store->put($ticket, $principal, $expiresIn);
        return ['ticket' => $ticket, 'expires_in' => $expiresIn];
    }
}
