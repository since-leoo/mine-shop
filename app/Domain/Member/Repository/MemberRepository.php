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

namespace App\Domain\Member\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Member\Member;

/**
 * @extends IRepository<Member>
 */
final class MemberRepository extends IRepository
{
    public function __construct(protected readonly Member $model) {}

    public function findByOpenid(string $openid): ?Member
    {
        return $this->model->newQuery()->where('openid', $openid)->first();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Member
    {
        return $this->model->newQuery()->create($payload);
    }

    public function updateLogin(int $id, string $ip): void
    {
        $this->getQuery()->whereKey($id)->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);
    }
}
