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

namespace App\Domain\Catalog\Product\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 面向 API 场景的商品查询领域服务.
 *
 * 继承 IService 获得 page()、count() 等基类方法.
 */
final class DomainApiProductQueryService extends IService
{
    public function __construct(
        public readonly ProductRepository $repository,
        private readonly ProductSnapshotInterface $productSnapshotService
    ) {}

    /**
     * 商品详情（含快照缓存）.
     *
     * @return null|array<string, mixed>
     */
    public function findById(mixed $id): ?array
    {
        return $this->productSnapshotService->getProduct($id, ['skus', 'attributes', 'gallery']);
    }
}
