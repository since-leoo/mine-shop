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

namespace App\Application\Admin\Content;

use App\Domain\Catalog\Category\Enum\CategoryStatus;
use App\Domain\Content\DiyPage\Repository\DiySelectorRepository;
use App\Domain\Trade\GroupBuy\Enum\GroupBuyStatus;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use App\Infrastructure\Model\Product\Product;

final class AppDiySelectorQueryService
{
    public function __construct(private readonly DiySelectorRepository $repository) {}

    public function products(array $params, int $page, int $pageSize): array
    {
        $params['status'] = Product::STATUS_ACTIVE;

        return $this->repository->products($params, $page, $pageSize);
    }

    public function categories(array $params): array
    {
        $params['status'] = CategoryStatus::ACTIVE->value;

        return $this->repository->categories($params);
    }

    public function coupons(array $params, int $page, int $pageSize): array
    {
        $params['status'] = 'active';

        return $this->repository->coupons($params, $page, $pageSize);
    }

    public function seckills(array $params, int $page, int $pageSize): array
    {
        $params['status'] ??= SeckillStatus::ACTIVE->value;
        $params['is_enabled'] = true;

        return $this->repository->seckills($params, $page, $pageSize);
    }

    public function groupBuys(array $params, int $page, int $pageSize): array
    {
        $params['status'] ??= GroupBuyStatus::ACTIVE->value;
        $params['is_enabled'] = true;

        return $this->repository->groupBuys($params, $page, $pageSize);
    }
}
