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

namespace App\Application\Admin\Infrastructure;

use App\Domain\Infrastructure\Attachment\Entity\AttachmentEntity;
use App\Domain\Infrastructure\Attachment\Repository\AttachmentRepository;

/**
 * 附件查询服务：处理所有读操作.
 */
final class AppAttachmentQueryService
{
    public function __construct(public readonly AttachmentRepository $repository) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?AttachmentEntity
    {
        return $this->repository->findEntityById($id);
    }

    public function findByHash(string $hash): ?AttachmentEntity
    {
        return $this->repository->findByHash($hash);
    }
}
