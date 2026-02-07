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

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Domain\Coupon\Service\DomainCouponUserService;

/**
 * 用户优惠券查询服务.
 */
final class AppCouponUserQueryService
{
    public function __construct(private readonly DomainCouponUserService $couponUserService) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->couponUserService->page($filters, $page, $pageSize);
    }

    public function find(int $id): CouponUserEntity
    {
        return $this->couponUserService->getEntity($id);
    }
}
