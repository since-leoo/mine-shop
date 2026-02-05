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
use App\Domain\Attachment\Trait\AttachmentMapperTrait;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Attachment\Attachment;
use Hyperf\Collection\Arr;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<Attachment>
 */
final class AttachmentRepository extends IRepository
{
    use AttachmentMapperTrait;

    public function __construct(protected readonly Attachment $model) {}

    public function create(array $payload): Attachment
    {
        return $this->model->newQuery()->create($payload);
    }

    public function findEntityById(int $id): ?AttachmentEntity
    {
        $model = parent::findById($id);
        return $model instanceof Attachment ? self::mapper($model) : null;
    }

    public function findByHash(string $hash): ?AttachmentEntity
    {
        /** @var ?Attachment $model */
        $model = Attachment::where('hash', $hash)->first();
        return $model ? self::mapper($model) : null;
    }

    public function save(AttachmentEntity $entity): AttachmentEntity
    {
        $info = $this->findById($entity->getId());
        $data = $entity->toArray();
        unset($data['id']);

        if ($info) {
            $info->fill($data)->save();
            return $entity;
        }

        $model = Attachment::create($data);
        $entity->setId((int) $model->id);
        return $entity;
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
}
