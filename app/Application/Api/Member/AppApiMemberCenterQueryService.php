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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Member\Api\Query\DomainApiMemberQueryService;
use App\Domain\Member\Service\DomainMemberReferralService;
use App\Domain\Trade\Order\Api\Query\DomainApiOrderQueryService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use App\Domain\Trade\Coupon\Api\Query\DomainApiCouponUserQueryService;

final class AppApiMemberCenterQueryService
{
    public function __construct(
        private readonly DomainApiMemberQueryService $memberQueryService,
        private readonly DomainApiOrderQueryService $orderQueryService,
        private readonly DomainApiCouponUserQueryService $couponUserQueryService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly DomainMemberReferralService $referralService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(int $memberId): array
    {
        $member = $this->memberQueryService->detail($memberId);
        if ($member === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        return $member;
    }

    /**
     * @return array{member: array, orderCounts: array, couponCount: int, servicePhone: string, referralCount: int}
     */
    public function overview(int $memberId): array
    {
        $member = $this->profile($memberId);
        $orderCounts = $this->orderQueryService->countByMemberStatuses($memberId);
        $couponCount = $this->couponUserQueryService->countByMember($memberId, 'unused');
        $servicePhone = $this->mallSettingService->order()->customerServicePhone();
        $referralCount = $this->referralService->referralCount($memberId);

        return [
            'member' => $member,
            'orderCounts' => $orderCounts,
            'couponCount' => $couponCount,
            'servicePhone' => $servicePhone,
            'referralCount' => $referralCount,
        ];
    }
}
