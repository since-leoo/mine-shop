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

namespace App\Application\Api\Coupon;

use App\Domain\Coupon\Api\CouponReadService;
use App\Domain\Coupon\Api\CouponUserReadService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Coupon\Coupon;
use App\Interface\Common\ResultCode;

final class CouponQueryApiService
{
    public function __construct(
        private readonly CouponReadService $readService,
        private readonly CouponUserReadService $couponUserReadService,
        private readonly CouponTransformer $transformer,
    ) {}

    /**
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function available(array $filters = [], ?int $memberId = null, int $limit = 20): array
    {
        $collection = $this->readService->listAvailable($filters, $limit);
        /** @var array<int, int> $couponIds */
        $couponIds = $collection->pluck('id')->all();

        $receivedMap = [];
        if ($memberId !== null && $couponIds !== []) {
            $receivedMap = $this->couponUserReadService->countByMemberForCoupons($memberId, $couponIds);
        }

        $list = $collection->map(function (Coupon $coupon) use ($receivedMap): array {
            $couponId = (int) $coupon->id;
            $received = $receivedMap[$couponId] ?? 0;
            return $this->transformer->transformListItem($coupon, $received);
        })->toArray();

        $total = $this->readService->countAvailable($filters);

        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    /**
     * @return array{detail: array<string, mixed>}
     */
    public function detail(int $id, ?int $memberId = null): array
    {
        $coupon = $this->readService->findOne($id);
        if ($coupon === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '优惠券不存在');
        }

        $received = $memberId !== null
            ? $this->couponUserReadService->countByMemberForCoupon($memberId, $id)
            : 0;

        return [
            'detail' => $this->transformer->transformDetail($coupon, $received),
        ];
    }
}
