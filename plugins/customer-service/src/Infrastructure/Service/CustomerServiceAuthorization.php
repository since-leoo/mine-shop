<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Service;

use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Error\CustomerServiceError;
use App\Infrastructure\Model\Permission\User;
use Plugin\CustomerService\Domain\Contract\CustomerServiceAuthorizationInterface;
use Plugin\CustomerService\Domain\ValueObject\SocketActionPolicy;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;

final class CustomerServiceAuthorization implements CustomerServiceAuthorizationInterface
{
    public function authorizeAgentAction(int $adminUserId, int $agentId, string $action): array
    {
        // Socket 是长连接；每次操作都重查账号、坐席和角色，权限撤销后无需等待重新登录。
        $permission = SocketActionPolicy::permission($action);
        $user = User::query()->find($adminUserId);
        $agent = CustomerServiceAgent::query()->whereKey($agentId)->where('admin_user_id', $adminUserId)->first();
        if ($user === null || $agent === null || ! $agent->enabled || $user->status !== Status::Normal) {
            throw CustomerServiceError::ACCOUNT_UNAVAILABLE->exception();
        }
        $roles = $user->roles()->where('role.status', Status::Normal);
        $superAdmin = (clone $roles)->where('code', 'SuperAdmin')->exists();
        $permitted = $superAdmin || (clone $roles)->whereHas('menus', static function ($query) use ($permission): void { $query->where('name', $permission)->where('status', Status::Normal); })->exists();
        if (! $permitted) {
            throw CustomerServiceError::ACTION_FORBIDDEN->exception();
        }
        return ['id' => (int) $agent->id, 'admin_user_id' => $adminUserId, 'display_name' => (string) $agent->display_name];
    }
}
