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

namespace App\Domain\Coupon\Api;

use App\Domain\Coupon\Repository\CouponRepository;
use App\Infrastructure\Model\Coupon\Coupon;
use Hyperf\Database\Model\Collection;

final class CouponReadService
{
    public function __construct(private readonly CouponRepository $couponRepository) {}

    /**
     * @return Collection<int, Coupon>
     */
    public function listAvailable(array $filters = [], int $limit = 20): Collection
    {
        return $this->couponRepository->listAvailable($filters, $limit);
    }

    public function countAvailable(array $filters = []): int
    {
        return $this->couponRepository->countAvailable($filters);
    }

    public function findOne(int $id): ?Coupon
    {
        /** @var Coupon|null $coupon */
        $coupon = $this->couponRepository->getQuery()->whereKey($id)->first();

        return $coupon;
    }
}
