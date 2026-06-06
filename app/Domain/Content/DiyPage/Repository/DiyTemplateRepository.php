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

namespace App\Domain\Content\DiyPage\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Content\DiyTemplate;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<DiyTemplate>
 */
class DiyTemplateRepository extends IRepository
{
    public function __construct(protected readonly DiyTemplate $model) {}

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $query = $this->handleSearch($this->getQuery(), $params)
            ->with(['category'])
            ->orderByDesc('sort')
            ->orderByDesc('id');
        $paginator = $query->paginate($pageSize, ['*'], self::PER_PAGE_PARAM_NAME, $page);

        return $this->handlePage($paginator);
    }

    public function createTemplate(array $data): DiyTemplate
    {
        /** @var DiyTemplate $template */
        $template = $this->create($data);

        return $template;
    }

    public function updateTemplate(int $id, array $data): bool
    {
        return $this->updateById($id, $data);
    }

    public function findDetail(int $id): ?DiyTemplate
    {
        /** @var DiyTemplate|null $template */
        $template = $this->getQuery()
            ->with(['category'])
            ->whereKey($id)
            ->first();

        return $template;
    }

    public function enable(int $id): bool
    {
        return $this->updateById($id, ['is_enabled' => true]);
    }

    public function disable(int $id): bool
    {
        return $this->updateById($id, ['is_enabled' => false]);
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(! empty($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(! empty($params['category_id']), static fn (Builder $q) => $q->where('category_id', (int) $params['category_id']))
            ->when(! empty($params['page_key']), static fn (Builder $q) => $q->where('page_key', $params['page_key']))
            ->when(! empty($params['page_type']), static fn (Builder $q) => $q->where('page_type', $params['page_type']))
            ->when(\array_key_exists('is_enabled', $params) && $params['is_enabled'] !== '', static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']));
    }

    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (DiyTemplate $item) => $item->toArray());
    }
}
