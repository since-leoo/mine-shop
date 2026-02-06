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

namespace App\Application\Query;

use App\Domain\Coupon\Entity\CouponEntity;
use App\Domain\Coupon\Service\CouponService;

/**
 * 优惠券查询服务.
 */
final class CouponQueryService
{
    public function __construct(private readonly CouponService $couponService) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->couponService->page($filters, $page, $pageSize);
    }

    /**
     * @throws \Exception
     */
    public function find(int $id): array
    {
        return $this->couponService->getEntity($id)->toArray();
    }

    /**
     * @throws \Exception
     */
    public function findEntity(int $id): CouponEntity
    {
        return $this->couponService->getEntity($id);
    }

    public function stats(): array
    {
        return $this->couponService->stats();
    }
}
