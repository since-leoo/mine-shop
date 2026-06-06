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

use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPageVersion;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<DiyPage>
 */
class DiyPageRepository extends IRepository
{
    public function __construct(protected readonly DiyPage $model) {}

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $query = $this->handleSearch($this->getQuery(), $params)
            ->with(['publishedVersion'])
            ->orderByDesc('id');
        $paginator = $query->paginate($pageSize, ['*'], self::PER_PAGE_PARAM_NAME, $page);

        return $this->handlePage($paginator);
    }

    public function findByPageKey(string $pageKey, string $pageType = DiyPageStatus::TYPE_ALL): ?DiyPage
    {
        /** @var DiyPage|null $page */
        $page = $this->getQuery()
            ->where('page_key', $pageKey)
            ->where('page_type', $pageType)
            ->first();

        return $page;
    }

    public function findDetail(int $id): ?DiyPage
    {
        /** @var DiyPage|null $page */
        $page = $this->getQuery()
            ->with([
                'publishedVersion',
                'versions' => static fn ($query) => $query->orderByDesc('id'),
            ])
            ->whereKey($id)
            ->first();

        return $page;
    }

    public function createPage(array $data): DiyPage
    {
        /** @var DiyPage $page */
        $page = $this->create($data);

        return $page;
    }

    public function updatePage(int $id, array $data): bool
    {
        return $this->updateById($id, $data);
    }

    public function copyPage(DiyPage $source, array $overrides): DiyPage
    {
        $data = array_merge([
            'page_key' => $source->page_key,
            'title' => $source->title . '副本',
            'page_type' => $source->page_type,
            'description' => $source->description,
            'is_enabled' => false,
            'status' => DiyPageStatus::PAGE_DISABLED,
            'published_version_id' => null,
        ], $overrides);

        $copy = $this->createPage($data);
        $version = DiyPageVersion::query()
            ->where('page_id', $source->id)
            ->orderByDesc('id')
            ->first();

        if ($version instanceof DiyPageVersion) {
            DiyPageVersion::query()->create([
                'page_id' => $copy->id,
                'version_no' => 1,
                'status' => DiyPageStatus::VERSION_DRAFT,
                'schema' => $version->schema,
                'created_by' => $overrides['created_by'] ?? null,
            ]);
        }

        return $copy;
    }

    public function disableSiblings(string $pageKey, string $pageType, int $exceptId): void
    {
        $this->getQuery()
            ->where('page_key', $pageKey)
            ->where('page_type', $pageType)
            ->where('id', '<>', $exceptId)
            ->update([
                'is_enabled' => false,
            ]);
    }

    public function findDraftVersion(int $pageId): ?DiyPageVersion
    {
        /** @var DiyPageVersion|null $version */
        $version = DiyPageVersion::query()
            ->where('page_id', $pageId)
            ->where('status', DiyPageStatus::VERSION_DRAFT)
            ->orderByDesc('id')
            ->first();

        return $version;
    }

    public function storeDraft(int $pageId, array $schema, ?int $operatorId): DiyPageVersion
    {
        $draft = $this->findDraftVersion($pageId);
        if ($draft instanceof DiyPageVersion) {
            $draft->fill([
                'schema' => $schema,
                'created_by' => $operatorId,
            ])->save();

            return $draft;
        }

        /** @var DiyPageVersion $version */
        $version = DiyPageVersion::query()->create([
            'page_id' => $pageId,
            'version_no' => $this->nextVersionNo($pageId),
            'status' => DiyPageStatus::VERSION_DRAFT,
            'schema' => $schema,
            'created_by' => $operatorId,
        ]);

        return $version;
    }

    public function nextVersionNo(int $pageId): int
    {
        return (int) DiyPageVersion::query()
            ->where('page_id', $pageId)
            ->max('version_no') + 1;
    }

    public function publishVersion(DiyPage $page, DiyPageVersion $version, ?int $operatorId): DiyPageVersion
    {
        DiyPageVersion::query()
            ->where('page_id', $page->id)
            ->where('status', DiyPageStatus::VERSION_PUBLISHED)
            ->where('id', '<>', $version->id)
            ->update(['status' => DiyPageStatus::VERSION_ARCHIVED]);

        $version->fill([
            'status' => DiyPageStatus::VERSION_PUBLISHED,
            'published_at' => Carbon::now(),
            'created_by' => $operatorId,
        ])->save();

        $page->fill([
            'status' => DiyPageStatus::PAGE_PUBLISHED,
            'published_version_id' => $version->id,
            'updated_by' => $operatorId,
        ])->save();

        return $version;
    }

    public function findPublishedByPageKey(string $pageKey, string $pageType = DiyPageStatus::TYPE_ALL): ?DiyPageVersion
    {
        /** @var DiyPageVersion|null $version */
        $version = DiyPageVersion::query()
            ->where('status', DiyPageStatus::VERSION_PUBLISHED)
            ->whereHas('page', static function (Builder $query) use ($pageKey, $pageType): void {
                $query
                    ->where('page_key', $pageKey)
                    ->where('page_type', $pageType)
                    ->where('is_enabled', true)
                    ->where('status', DiyPageStatus::PAGE_PUBLISHED);
            })
            ->orderByDesc('id')
            ->first();

        return $version;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(! empty($params['title']), static fn (Builder $q) => $q->where('title', 'like', '%' . $params['title'] . '%'))
            ->when(! empty($params['page_key']), static fn (Builder $q) => $q->where('page_key', 'like', '%' . $params['page_key'] . '%'))
            ->when(! empty($params['page_type']), static fn (Builder $q) => $q->where('page_type', $params['page_type']))
            ->when(\array_key_exists('is_enabled', $params) && $params['is_enabled'] !== '', static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']));
    }

    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (DiyPage $item) => $item->toArray());
    }
}
