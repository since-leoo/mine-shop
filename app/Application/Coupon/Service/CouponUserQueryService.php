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

namespace App\Application\Coupon\Service;

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Domain\Coupon\Service\CouponUserService;

/**
 * 用户优惠券查询服务.
 */
final class CouponUserQueryService
{
    public function __construct(private readonly CouponUserService $couponUserService) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->couponUserService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?CouponUserEntity
    {
        $entity = $this->couponUserService->findById($id);

        if (! $entity) {
            throw new \InvalidArgumentException('用户优惠券不存在');
        }

        return $entity;
    }
}
