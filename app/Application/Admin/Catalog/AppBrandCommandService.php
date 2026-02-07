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

namespace App\Application\Admin\Catalog;

use App\Domain\Catalog\Brand\Contract\BrandInput;
use App\Domain\Catalog\Brand\Service\DomainBrandService;
use App\Infrastructure\Model\Product\Brand;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\DbConnection\Db;

/**
 * 品牌命令服务：处理所有写操作.
 */
final class AppBrandCommandService
{
    public function __construct(
        private readonly DomainBrandService $brandService,
    ) {}

    #[CacheEvict(prefix: 'brands', all: true)]
    public function create(BrandInput $input): Brand
    {
        return Db::transaction(fn () => $this->brandService->create($input));
    }

    #[CacheEvict(prefix: 'brands', all: true)]
    public function update(BrandInput $input): bool
    {
        return Db::transaction(fn () => $this->brandService->update($input));
    }

    #[CacheEvict(prefix: 'brands', all: true)]
    public function delete(int $id): bool
    {
        return Db::transaction(fn () => $this->brandService->delete($id));
    }

    #[CacheEvict(prefix: 'brands', all: true)]
    public function updateSort(array $sortData): bool
    {
        return Db::transaction(fn () => $this->brandService->updateSort($sortData));
    }
}
