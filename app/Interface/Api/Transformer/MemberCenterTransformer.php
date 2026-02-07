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

namespace App\Interface\Api\Transformer;

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
                'icon_name' => 'wallet',
                'order_num' => $pending,
                'tab_type' => 5,
                'status' => 1,
            ],
            [
                'title' => '待发货',
                'icon_name' => 'deliver',
                'order_num' => $paid,
                'tab_type' => 10,
                'status' => 1,
            ],
            [
                'title' => '待收货',
                'icon_name' => 'package',
                'order_num' => $shipped,
                'tab_type' => 40,
                'status' => 1,
            ],
            [
                'title' => '待评价',
                'icon_name' => 'comment',
                'order_num' => $completed,
                'tab_type' => 60,
                'status' => 1,
            ],
            [
                'title' => '退款/售后',
                'icon_name' => 'exchang',
                'order_num' => $afterSale,
                'tab_type' => 0,
                'status' => 1,
            ],
        ];

        return [
            'user_info' => [
                'avatar' => $profile['avatar'],
                'nickname' => $profile['nickname'],
                'phone' => $profile['phone'],
                'gender' => $profile['gender'],
                'level_name' => $profile['level_name'],
                'authorized_profile' => $profile['authorized_profile'],
                'balance' => $profile['balance'],
                'points' => $profile['points'],
            ],
            'counts_data' => [
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
            'order_tag_infos' => $orderTagInfos,
            'customer_service_info' => [
                'service_phone' => $servicePhone,
                'service_time_duration' => '每日 09:00-21:00',
            ],
        ];
    }
}
