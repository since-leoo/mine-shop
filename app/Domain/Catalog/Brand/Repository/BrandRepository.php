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

namespace App\Domain\Catalog\Brand\Repository;

use App\Domain\Catalog\Brand\Entity\BrandEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Product\Brand;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

/**
 * @extends IRepository<Brand>
 */
final class BrandRepository extends IRepository
{
    public function __construct(protected readonly Brand $model) {}

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $query = $this->handleSearch($this->getQuery(), $params)
            ->orderBy('sort')
            ->orderBy('id');
        $paginator = $query->paginate($pageSize, ['*'], self::PER_PAGE_PARAM_NAME, $page);

        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
        ];
    }

    public function store(BrandEntity $entity): Brand
    {
        $brand = Brand::create($entity->toArray());
        $entity->setId($brand->id);
        return $brand;
    }

    public function update(BrandEntity $entity): bool
    {
        $brand = Brand::find($entity->getId());
        return $brand ? $brand->update($entity->toArray()) : false;
    }

    public function getOptions(): array
    {
        return Brand::getOptions();
    }

    public function updateSort(array $sortData): bool
    {
        return (bool) Db::transaction(static function () use ($sortData) {
            foreach ($sortData as $item) {
                Brand::where('id', $item['id'])->update(['sort' => $item['sort']]);
            }
            return true;
        });
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']));
    }
}
