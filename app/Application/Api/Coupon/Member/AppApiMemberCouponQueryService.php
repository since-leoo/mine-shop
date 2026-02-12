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

namespace App\Application\Api\Coupon\Member;

use App\Domain\Trade\Coupon\Api\Query\DomainApiCouponUserQueryService;

final class AppApiMemberCouponQueryService
{
    public function __construct(private readonly DomainApiCouponUserQueryService $couponUserQueryService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $memberId, string $status): array
    {
        return $this->couponUserQueryService->listByMember($memberId, $status);
    }
}
