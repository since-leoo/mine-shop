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

namespace App\Application\Logstash\Service;

use App\Domain\Logstash\Repository\UserOperationLogRepository;
use App\Domain\Shared\ValueObject\PageQuery;

final class UserOperationLogService
{
    public function __construct(private readonly UserOperationLogRepository $repository) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): mixed
    {
        return $this->repository->create($payload);
    }

    public function paginate(PageQuery $query): array
    {
        return $this->repository->page(
            $query->getFilters(),
            $query->getPage(),
            $query->getPageSize()
        );
    }

    public function delete(mixed $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }
}
