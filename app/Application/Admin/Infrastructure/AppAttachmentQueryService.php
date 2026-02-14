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
use App\Domain\Infrastructure\Attachment\Service\DomainAttachmentService;

/**
 * 附件查询服务：处理所有读操作.
 */
final class AppAttachmentQueryService
{
    public function __construct(
        private readonly DomainAttachmentService $attachmentService,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->attachmentService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?AttachmentEntity
    {
        return $this->attachmentService->findEntity($id);
    }

    public function findByHash(string $hash): ?AttachmentEntity
    {
        return $this->attachmentService->findByHash($hash);
    }
}
