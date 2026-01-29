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

namespace App\Application\Product\Service;

use App\Domain\Product\Entity\BrandEntity;
use App\Domain\Product\Service\BrandService;
use App\Infrastructure\Model\Product\Brand;
use Hyperf\Cache\Annotation\CacheEvict;

/**
 * 品牌命令服务：处理所有写操作.
 */
final class BrandCommandService
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly BrandQueryService $queryService
    ) {}

    #[CacheEvict(prefix: 'mall:brands', all: true)]
    public function create(BrandEntity $entity): Brand
    {
        return $this->brandService->create($entity);
    }

    #[CacheEvict(prefix: 'mall:brands', all: true)]
    public function update(BrandEntity $entity): bool
    {
        $brand = $this->queryService->find($entity->getId());
        $brand || throw new \InvalidArgumentException('品牌不存在');
        return $this->brandService->update($entity);
    }

    #[CacheEvict(prefix: 'mall:brands', all: true)]
    public function delete(int $id): bool
    {
        $brand = $this->queryService->find($id);
        $brand || throw new \InvalidArgumentException('品牌不存在');
        $brand->canDelete() || throw new \RuntimeException('该品牌下还有商品，无法删除');
        return $this->brandService->delete($id);
    }

    #[CacheEvict(prefix: 'mall:brands', all: true)]
    public function updateSort(array $sortData): bool
    {
        return $this->brandService->updateSort($sortData);
    }
}
