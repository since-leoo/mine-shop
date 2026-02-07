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

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderItemEntity;
use App\Domain\Order\ValueObject\OrderAddressValue;
use App\Domain\SystemSetting\Service\DomainMallSettingService;

final class OrderCheckoutTransformer
{
    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

    public function transform(OrderEntity $order): array
    {
        $storeName = $this->mallSettingService->basic()->mallName();
        $price = $order->getPriceDetail();

        $items = array_map(
            static fn (OrderItemEntity $item): array => $item->toArray(),
            $order->getItems(),
        );

        $goodsCount = array_sum(array_map(
            static fn (OrderItemEntity $item) => $item->getQuantity(),
            $order->getItems(),
        ));

        return [
            'settle_type' => $order->getAddress() ? 1 : 0,
            'user_address' => $this->formatAddress($order->getAddress()),
            'store_name' => $storeName,
            'goods_count' => $goodsCount,
            'items' => $items,
            'price' => $price?->toArray() ?? [
                'goods_amount' => 0,
                'discount_amount' => 0,
                'shipping_fee' => 0,
                'total_amount' => 0,
                'pay_amount' => 0,
            ],
            'coupon_amount' => $order->getCouponAmount(),
            'invoice_support' => $this->mallSettingService->order()->enableInvoice() ? 1 : 0,
        ];
    }

    private function formatAddress(?OrderAddressValue $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'name' => $address->getReceiverName(),
            'phone' => $address->getReceiverPhone(),
            'province' => $address->getProvince(),
            'city' => $address->getCity(),
            'district' => $address->getDistrict(),
            'detail_address' => $address->getDetail(),
            'full_address' => $address->getFullAddress(),
            'checked' => true,
        ];
    }
}
