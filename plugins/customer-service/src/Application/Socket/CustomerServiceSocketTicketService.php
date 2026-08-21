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

use App\Infrastructure\Abstract\ICache;

final class CustomerServiceSocketTicketService
{
    public function __construct(private readonly ICache $cache)
    {
        $this->cache->setPrefix('customer-service:socket-ticket');
    }

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
        $payload = $this->cache->get($ticket);
        $this->cache->delete($ticket);
        $decoded = \is_string($payload) ? json_decode($payload, true) : null;
        if (! \is_array($decoded) || ! isset($decoded['principal_type'], $decoded['principal_id'])) {
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
        $ticket = bin2hex(random_bytes(24));
        $expiresIn = 60;
        $this->cache->set($ticket, json_encode($principal, \JSON_THROW_ON_ERROR), ['EX' => $expiresIn]);
        return ['ticket' => $ticket, 'expires_in' => $expiresIn];
    }
}
