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

namespace App\Domain\Infrastructure\Attachment\Service;

use App\Domain\Infrastructure\Attachment\Entity\AttachmentEntity;
use App\Domain\Infrastructure\Attachment\Repository\AttachmentRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 附件领域服务：封装附件查询与持久化逻辑.
 */
final class DomainAttachmentService extends IService
{
    public function __construct(
        private readonly AttachmentRepository $repository,
    ) {}

    public function findEntity(int $id): ?AttachmentEntity
    {
        return $this->repository->findEntityById($id);
    }

    public function findByHash(string $hash): ?AttachmentEntity
    {
        return $this->repository->findByHash($hash);
    }

    public function save(AttachmentEntity $entity): AttachmentEntity
    {
        return $this->repository->save($entity);
    }

    public function deleteByIds(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }
}
