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

namespace App\Domain\Member\Api\Query;

use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 面向 API 场景的会员查询领域服务.
 */
final class DomainApiMemberQueryService extends IService
{
    public function __construct(public readonly MemberRepository $repository) {}

    /**
     * 会员详情.
     *
     * @return null|array<string, mixed>
     */
    public function detail(int $memberId): ?array
    {
        return $this->repository->detail($memberId);
    }
}
