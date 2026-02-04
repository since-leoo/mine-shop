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

final class MemberCenterTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function transform(array $profile, array $orderCounts, int $couponCount, string $servicePhone): array
    {
        [$pending, $paid, $shipped, $completed, $afterSale] = $orderCounts;
        $orderTagInfos = [
            [
                'title' => '待付款',
                'iconName' => 'wallet',
                'orderNum' => $pending,
                'tabType' => 5,
                'status' => 1,
            ],
            [
                'title' => '待发货',
                'iconName' => 'deliver',
                'orderNum' => $paid,
                'tabType' => 10,
                'status' => 1,
            ],
            [
                'title' => '待收货',
                'iconName' => 'package',
                'orderNum' => $shipped,
                'tabType' => 40,
                'status' => 1,
            ],
            [
                'title' => '待评价',
                'iconName' => 'comment',
                'orderNum' => $completed,
                'tabType' => 60,
                'status' => 1,
            ],
            [
                'title' => '退款/售后',
                'iconName' => 'exchang',
                'orderNum' => $afterSale,
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
                'authorizedProfile' => $profile['nickName'],
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
                'servicePhone' => $servicePhone,
                'serviceTimeDuration' => '每日 09:00-21:00',
            ],
        ];
    }
}
