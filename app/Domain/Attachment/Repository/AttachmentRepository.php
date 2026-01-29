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

namespace App\Domain\Attachment\Repository;

use App\Domain\Attachment\Entity\AttachmentEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Attachment\Attachment;
use Hyperf\Collection\Arr;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<Attachment>
 */
final class AttachmentRepository extends IRepository
{
    public function __construct(protected readonly Attachment $model) {}

    public function create(array $payload): Attachment
    {
        return $this->model->newQuery()->create($payload);
    }

    public function findEntityById(int $id): ?AttachmentEntity
    {
        $model = parent::findById($id);
        return $model instanceof Attachment ? $this->mapToEntity($model) : null;
    }

    public function findByHash(string $hash): ?AttachmentEntity
    {
        $model = Attachment::where('hash', $hash)->first();
        return $model ? $this->mapToEntity($model) : null;
    }

    public function save(AttachmentEntity $entity): AttachmentEntity
    {
        $data = $entity->toArray();
        unset($data['id']);

        if ($entity->getId() > 0) {
            Attachment::where('id', $entity->getId())->update($data);
            return $entity;
        }

        $model = Attachment::create($data);
        $entity->setId((int) $model->id);
        return $entity;
    }

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $query = $this->handleSearch($this->getQuery(), $params)->orderByDesc('id');
        $paginator = $query->paginate($pageSize, ['*'], self::PER_PAGE_PARAM_NAME, $page);

        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
        ];
    }

    public function deleteByIds(array $ids): int
    {
        return Attachment::destroy($ids);
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(Arr::get($params, 'suffix'), static fn (Builder $q, $v) => $q->whereIn('suffix', Arr::wrap($v)))
            ->when(Arr::get($params, 'mime_type'), static fn (Builder $q, $v) => $q->whereIn('mime_type', Arr::wrap($v)))
            ->when(Arr::get($params, 'storage_mode'), static fn (Builder $q, $v) => $q->whereIn('storage_mode', Arr::wrap($v)))
            ->when(Arr::get($params, 'created_by'), static fn (Builder $q, $v) => $q->where('created_by', $v))
            ->when(Arr::get($params, 'created_at'), static fn (Builder $q, $v) => $q->whereBetween('created_at', $v))
            ->when(Arr::get($params, 'url'), static fn (Builder $q, $v) => $q->where('url', $v))
            ->when(Arr::get($params, 'hash'), static fn (Builder $q, $v) => $q->where('hash', $v))
            ->when(Arr::get($params, 'origin_name'), static fn (Builder $q, $v) => $q->where('origin_name', 'like', '%' . $v . '%'));
    }

    private function mapToEntity(Attachment $model): AttachmentEntity
    {
        return (new AttachmentEntity())
            ->setId((int) $model->id)
            ->setCreatedBy((int) $model->created_by)
            ->setOriginName((string) $model->origin_name)
            ->setStorageMode((string) $model->storage_mode)
            ->setObjectName((string) $model->object_name)
            ->setMimeType((string) $model->mime_type)
            ->setStoragePath((string) $model->storage_path)
            ->setHash((string) $model->hash)
            ->setSuffix((string) $model->suffix)
            ->setSizeByte((int) $model->size_byte)
            ->setSizeInfo((string) $model->size_info)
            ->setUrl((string) $model->url);
    }
}
