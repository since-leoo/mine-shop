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

namespace Plugin\Since\Coupon\Interface\Request\Admin;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;
use Plugin\Since\Coupon\Domain\Contract\CouponUserInput;
use Plugin\Since\Coupon\Interface\Dto\Admin\CouponUserDto;

class CouponUserRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'coupon_id' => ['nullable', 'integer', 'min:1'],
            'member_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['unused', 'used', 'expired'])],
            'keyword' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function markUsedRules(): array
    {
        return [];
    }

    public function markExpiredRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'coupon_id' => '优惠券ID',
            'member_id' => '会员ID',
            'status' => '状态',
            'keyword' => '搜索关键词',
        ];
    }

    /**
     * 转换为DTO.
     */
    public function toDto(?int $id = null): CouponUserInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        // Convert snake_case keys to camelCase for DTO mapping
        if (isset($params['coupon_id'])) {
            $params['couponId'] = $params['coupon_id'];
            unset($params['coupon_id']);
        }

        if (isset($params['member_id'])) {
            $params['memberId'] = $params['member_id'];
            unset($params['member_id']);
        }

        if (isset($params['order_id'])) {
            $params['orderId'] = $params['order_id'];
            unset($params['order_id']);
        }

        if (isset($params['received_at'])) {
            $params['receivedAt'] = $params['received_at'];
            unset($params['received_at']);
        }

        if (isset($params['used_at'])) {
            $params['usedAt'] = $params['used_at'];
            unset($params['used_at']);
        }

        if (isset($params['expire_at'])) {
            $params['expireAt'] = $params['expire_at'];
            unset($params['expire_at']);
        }

        return Mapper::map($params, new CouponUserDto());
    }
}
