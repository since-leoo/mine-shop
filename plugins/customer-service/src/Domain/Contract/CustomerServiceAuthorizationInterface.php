<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

interface CustomerServiceAuthorizationInterface
{
    /** @return array{id:int, admin_user_id:int, display_name:string} */
    public function authorizeAgentAction(int $adminUserId, int $agentId, string $action): array;
}
