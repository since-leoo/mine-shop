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

namespace App\Domain\Member\Api;

use App\Domain\Coupon\Repository\CouponUserRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\SystemSetting\Service\MallSettingService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class MemberCenterReadService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly CouponUserRepository $couponUserRepository,
        private readonly OrderRepository $orderRepository,
        private readonly MallSettingService $mallSettingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(int $memberId): array
    {
        $member = $this->memberRepository->detail($memberId);
        if ($member === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        return [
            'id' => $member['id'],
            'avatarUrl' => $member['avatar'] ?? null,
            'nickName' => $member['nickname'] ?? '',
            'phoneNumber' => $member['phone'] ?? '',
            'gender' => $member['gender'] ?? 'unknown',
            'levelName' => $member['level_definition']['name'] ?? null,
            'level' => $member['level'] ?? null,
            'balance' => (float) ($member['wallet']['balance'] ?? 0),
            'points' => (int) ($member['points_wallet']['balance'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(int $memberId): array
    {
        $profile = $this->profile($memberId);
        $couponCount = $this->couponUserRepository->countByMember($memberId, 'unused');

        $orderCounts = [
            'pending' => $this->orderRepository->countByMemberAndStatuses($memberId, [OrderStatus::PENDING->value]),
            'paid' => $this->orderRepository->countByMemberAndStatuses($memberId, [OrderStatus::PAID->value]),
            'shipping' => $this->orderRepository->countByMemberAndStatuses($memberId, [
                OrderStatus::PARTIAL_SHIPPED->value,
                OrderStatus::SHIPPED->value,
            ]),
            'comment' => $this->orderRepository->countByMemberAndStatuses($memberId, [OrderStatus::COMPLETED->value]),
            'afterSale' => $this->orderRepository->countByMemberAndStatuses($memberId, [
                OrderStatus::REFUNDED->value,
                OrderStatus::CANCELLED->value,
            ]),
        ];

        $orderTagInfos = [
            [
                'title' => '待付款',
                'iconName' => 'wallet',
                'orderNum' => $orderCounts['pending'],
                'tabType' => 5,
                'status' => 1,
            ],
            [
                'title' => '待发货',
                'iconName' => 'deliver',
                'orderNum' => $orderCounts['paid'],
                'tabType' => 10,
                'status' => 1,
            ],
            [
                'title' => '待收货',
                'iconName' => 'package',
                'orderNum' => $orderCounts['shipping'],
                'tabType' => 40,
                'status' => 1,
            ],
            [
                'title' => '待评价',
                'iconName' => 'comment',
                'orderNum' => $orderCounts['comment'],
                'tabType' => 60,
                'status' => 1,
            ],
            [
                'title' => '退款/售后',
                'iconName' => 'exchang',
                'orderNum' => $orderCounts['afterSale'],
                'tabType' => 0,
                'status' => 1,
            ],
        ];

        return [
            'userInfo' => [
                'avatarUrl' => $profile['avatarUrl'],
                'nickName' => $profile['nickName'],
                'phoneNumber' => $profile['phoneNumber'],
                'gender' => $profile['gender'],
                'level' => $profile['levelName'],
            ],
            'countsData' => [
                [
                    'num' => $profile['points'],
                    'name' => '积分',
                    'type' => 'point',
                ],
                [
                    'num' => $couponCount,
                    'name' => '优惠券',
                    'type' => 'coupon',
                ],
            ],
            'orderTagInfos' => $orderTagInfos,
            'customerServiceInfo' => [
                'servicePhone' => $this->mallSettingService->order()->customerServicePhone(),
                'serviceTimeDuration' => '每日 09:00-21:00',
            ],
        ];
    }
}
