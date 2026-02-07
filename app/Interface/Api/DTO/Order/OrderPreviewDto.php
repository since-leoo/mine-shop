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

namespace App\Interface\Api\DTO\Order;

use App\Domain\Order\Contract\OrderPreviewInput;

/**
 * 订单预览 DTO.
 */
class OrderPreviewDto implements OrderPreviewInput
{
    public int $member_id = 0;

    public string $order_type = 'normal';

    public array $goods_request_list = [];

    public ?int $address_id = null;

    public ?array $user_address = null;

    public ?array $coupon_list = null;

    public ?array $store_info_list = null;

    public function getMemberId(): int
    {
        return $this->member_id;
    }

    public function getOrderType(): string
    {
        return $this->order_type;
    }

    /**
     * @return array<int, array{sku_id: int, quantity: int}>
     */
    public function getGoodsRequestList(): array
    {
        return $this->goods_request_list;
    }

    public function getAddressId(): ?int
    {
        return $this->address_id;
    }

    /**
     * @return ?array<string, mixed>
     */
    public function getUserAddress(): ?array
    {
        return $this->user_address;
    }

    /**
     * @return ?array<int, array{coupon_id: int}>
     */
    public function getCouponList(): ?array
    {
        return $this->coupon_list;
    }

    /**
     * 从 store_info_list 提取买家备注.
     *
     * 取第一个店铺信息中的 remark 字段，若不存在则返回空字符串。
     */
    public function getBuyerRemark(): string
    {
        if (empty($this->store_info_list)) {
            return '';
        }

        return (string) ($this->store_info_list[0]['remark'] ?? '');
    }
}
