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

namespace App\Application\Api\Order;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderItemEntity;
use App\Domain\Order\ValueObject\OrderAddressValue;
use App\Domain\SystemSetting\Service\MallSettingService;

final class OrderCheckoutTransformer
{
    public function __construct(private readonly MallSettingService $mallSettingService) {}

    public function transform(OrderEntity $order): array
    {
        $storeName = $this->mallSettingService->basic()->mallName();
        $items = array_map(
            fn (OrderItemEntity $item): array => $this->formatGoodsDetail($item, $storeName),
            $order->getItems(),
        );

        $goodsCount = array_sum(array_map(static fn (OrderItemEntity $item) => $item->getQuantity(), $order->getItems()));
        $price = $order->getPriceDetail();
        $goodsAmount = $price?->getGoodsAmount() ?? 0.0;
        $payAmount = $price?->getPayAmount() ?? $goodsAmount;

        $storeGoodsList = [];
        if ($items !== []) {
            $storeGoodsList[] = [
                'store_id' => '1',
                'store_name' => $storeName,
                'remark' => '',
                'goods_count' => \count($items),
                'delivery_fee' => $this->toCent($price?->getShippingFee() ?? 0),
                'delivery_words' => null,
                'store_total_amount' => $this->toCent($goodsAmount),
                'store_total_pay_amount' => $this->toCent($payAmount),
                'store_total_discount_amount' => $this->toCent($price?->getDiscountAmount() ?? 0),
                'store_total_coupon_amount' => 0,
                'sku_detail_list' => $items,
                'coupon_list' => [],
            ];
        }

        return [
            'settle_type' => $order->getAddress() ? 1 : 0,
            'user_address' => $this->formatAddress($order->getAddress()),
            'total_goods_count' => $goodsCount,
            'package_count' => $items === [] ? 0 : 1,
            'total_amount' => $this->toCent($goodsAmount),
            'total_pay_amount' => $this->toCent($payAmount),
            'total_discount_amount' => $this->toCent($price?->getDiscountAmount() ?? 0),
            'total_promotion_amount' => 0,
            'total_coupon_amount' => 0,
            'total_sale_price' => $this->toCent($goodsAmount),
            'total_goods_amount' => $this->toCent($goodsAmount),
            'total_delivery_fee' => $this->toCent($price?->getShippingFee() ?? 0),
            'invoice_request' => null,
            'sku_images' => null,
            'delivery_fee_list' => null,
            'store_goods_list' => $storeGoodsList,
            'invalid_goods_list' => [],
            'out_of_stock_goods_list' => [],
            'limit_goods_list' => [],
            'abnormal_delivery_goods_list' => [],
            'invoice_support' => $this->mallSettingService->order()->enableInvoice() ? 1 : 0,
        ];
    }

    private function formatGoodsDetail(OrderItemEntity $item, string $storeName): array
    {
        $unitPrice = $item->getUnitPrice();
        $totalPrice = $item->getTotalPrice();

        return [
            'store_id' => '1',
            'store_name' => $storeName,
            'spu_id' => (string) $item->getProductId(),
            'sku_id' => (string) $item->getSkuId(),
            'goods_name' => $item->getProductName(),
            'image' => $item->getProductImage(),
            'reminder_stock' => 9999,
            'quantity' => $item->getQuantity(),
            'pay_price' => $this->toCent($unitPrice),
            'total_sku_price' => $this->toCent($totalPrice),
            'discount_settle_price' => $this->toCent($unitPrice),
            'real_settle_price' => $this->toCent($unitPrice),
            'settle_price' => $this->toCent($unitPrice),
            'origin_price' => $this->toCent($unitPrice),
            'tag_price' => null,
            'tag_text' => null,
            'sku_spec_list' => $this->formatSpecInfo($item->getSpecValues()),
            'promotion_ids' => null,
            'weight' => $item->getWeight(),
            'unit' => 'KG',
            'master_goods_type' => 0,
            'vice_goods_type' => 0,
            'room_id' => null,
            'egoods_name' => null,
            'uid' => '',
            'saas_id' => 'mine-mall',
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

    /**
     * @return array<int, array{spec_title:string,spec_value:string}>
     */
    private function formatSpecInfo(mixed $values): array
    {
        if (! \is_array($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $value) {
            if (\is_array($value)) {
                $result[] = [
                    'spec_title' => (string) ($value['title'] ?? $value['name'] ?? $value['specTitle'] ?? ''),
                    'spec_value' => (string) ($value['value'] ?? $value['specValue'] ?? ''),
                ];
            } elseif (\is_string($value) && $value !== '') {
                $parts = preg_split('/[:：]/', $value);
                $result[] = [
                    'spec_title' => (string) ($parts[0] ?? ''),
                    'spec_value' => (string) ($parts[1] ?? $value),
                ];
            }
        }

        return $result;
    }

    private function toCent(float $price): int
    {
        return (int) round($price * 100);
    }
}
