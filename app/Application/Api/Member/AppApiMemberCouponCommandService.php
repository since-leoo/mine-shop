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

use App\Domain\Coupon\Api\Command\DomainApiCouponUserCommandService;
use App\Infrastructure\Model\Coupon\CouponUser;
use Hyperf\DbConnection\Db;

final class AppApiMemberCouponCommandService
{
    public function __construct(
        private readonly DomainApiCouponUserCommandService $couponUserCommandService,
    ) {}

    /**
     * 会员领取优惠券.
     */
    public function receive(int $memberId, int $couponId): CouponUser
    {
        // 事务管理
        return Db::transaction(fn () => $this->couponUserCommandService->receive($memberId, $couponId));
    }
}
