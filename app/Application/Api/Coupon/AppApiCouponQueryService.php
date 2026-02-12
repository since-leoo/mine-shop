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

use App\Domain\Trade\Coupon\Api\Query\DomainApiCouponQueryService;
use App\Domain\Trade\Coupon\Api\Query\DomainApiCouponUserQueryService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Coupon\Coupon;
use App\Interface\Common\ResultCode;
use Hyperf\Database\Model\Collection;

final class AppApiCouponQueryService
{
    public function __construct(
        private readonly DomainApiCouponQueryService $couponQueryService,
        private readonly DomainApiCouponUserQueryService $couponUserQueryService,
    ) {}

    /**
     * @return array{collection: Collection, receivedMap: array<int, int>, total: int}
     */
    public function available(array $filters = [], ?int $memberId = null, int $limit = 20): array
    {
        $collection = $this->couponQueryService->listAvailable($filters, $limit);

        $receivedMap = [];
        if ($memberId !== null) {
            $couponIds = $collection->pluck('id')->all();
            if ($couponIds !== []) {
                $receivedMap = $this->couponUserQueryService->countByMemberForCoupons($memberId, $couponIds);
            }
        }

        $total = $this->couponQueryService->countAvailable($filters);

        return [
            'collection' => $collection,
            'receivedMap' => $receivedMap,
            'total' => $total,
        ];
    }

    /**
     * @return array{coupon: Coupon, receivedQuantity: int}
     */
    public function detail(int $id, ?int $memberId = null): array
    {
        $coupon = $this->couponQueryService->findOne($id);
        if ($coupon === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '优惠券不存在');
        }

        $received = $memberId !== null
            ? $this->couponUserQueryService->countByMemberForCoupon($memberId, $id)
            : 0;

        return [
            'coupon' => $coupon,
            'receivedQuantity' => $received,
        ];
    }
}
