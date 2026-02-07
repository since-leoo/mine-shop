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

namespace App\Application\Admin\Marketing;

use App\Domain\Marketing\Coupon\Service\DomainCouponService;

/**
 * 优惠券查询服务.
 */
final class AppCouponQueryService
{
    public function __construct(private readonly DomainCouponService $couponService) {}

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

    public function stats(): array
    {
        return $this->couponService->stats();
    }
}
