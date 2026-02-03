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

namespace App\Application\Api\Member;

use App\Domain\Coupon\Api\CouponUserReadService;

final class MemberCouponApiService
{
    public function __construct(private readonly CouponUserReadService $couponUserReadService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $memberId, string $status): array
    {
        return $this->couponUserReadService->listByMember($memberId, $status);
    }
}
