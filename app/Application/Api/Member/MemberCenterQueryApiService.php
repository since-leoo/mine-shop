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
use App\Domain\Member\Service\MemberService;
use App\Domain\Order\Service\OrderService;
use App\Domain\SystemSetting\Service\MallSettingService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class MemberCenterQueryApiService
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly OrderService $orderService,
        private readonly CouponUserService $couponUserService,
        private readonly MallSettingService $mallSettingService,
        private readonly MemberProfileTransformer $profileTransformer,
        private readonly MemberCenterTransformer $centerTransformer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(int $memberId): array
    {
        $member = $this->memberService->detail($memberId);
        if ($member === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        return $this->profileTransformer->transform($member);
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

        return $this->centerTransformer->transform($profile, $orderCounts, $couponCount, $servicePhone);
    }
}
