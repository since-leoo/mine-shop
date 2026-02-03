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
use App\Domain\Coupon\Repository\CouponUserRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class CouponQueryApiService
{
    public function __construct(
        private readonly CouponReadService $readService,
        private readonly CouponTransformer $transformer,
        private readonly CouponUserRepository $couponUserRepository
    ) {}

    public function available(array $filters = [], ?int $memberId = null, int $limit = 20): array
    {
        $collection = $this->readService->listAvailable($filters, $limit);
        $couponIds = $collection->pluck('id')->all();

        $receivedMap = [];
        if ($memberId !== null && $couponIds !== []) {
            $receivedMap = $this->couponUserRepository->countByMemberForCoupons($memberId, $couponIds);
        }

        $list = $collection->map(function ($coupon) use ($receivedMap): array {
            $couponId = (int) $coupon->id;
            $received = $receivedMap[$couponId] ?? 0;
            return $this->transformer->transformListItem($coupon, $received);
        })->toArray();

        return [
            'list' => $list,
            'total' => $collection->count(),
        ];
    }

    public function detail(int $id, ?int $memberId = null): array
    {
        $coupon = $this->readService->findOne($id);
        if ($coupon === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '优惠券不存在');
        }

        $received = 0;
        if ($memberId !== null) {
            $received = (int) ($this->couponUserRepository->countByMemberForCoupons($memberId, [$id])[$id] ?? 0);
        }

        return [
            'detail' => $this->transformer->transformDetail($coupon, $received),
        ];
    }
}
