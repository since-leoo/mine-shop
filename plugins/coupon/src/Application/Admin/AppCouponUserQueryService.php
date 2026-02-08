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

namespace Plugin\Since\Coupon\Application\Admin;

use Plugin\Since\Coupon\Domain\Entity\CouponUserEntity;
use Plugin\Since\Coupon\Domain\Service\DomainCouponUserService;

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
