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

use App\Domain\Coupon\Service\CouponUserService;
use App\Domain\Member\Api\MemberCenterReadService;
use App\Domain\Order\Service\OrderService;
use App\Domain\SystemSetting\Service\MallSettingService;

final class MemberCenterQueryApiService
{
    public function __construct(
        private readonly MemberCenterReadService $readService,
        private readonly OrderService $orderService,
        private readonly CouponUserService $couponUserService,
        private readonly MallSettingService $mallSettingService,
        private readonly MemberCenterTransformer $transformer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(int $memberId): array
    {
        return $this->readService->profile($memberId);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(int $memberId): array
    {
        $profile = $this->profile($memberId);

        $orderCounts = $this->orderService->countByMemberAndStatuses($memberId);

        $couponCount = $this->couponUserService->countByMember($memberId, 'unused');

        $servicePhone = $this->mallSettingService->order()->customerServicePhone();

        return $this->transformer->transform($profile, $orderCounts, $couponCount, $servicePhone);
    }
}
