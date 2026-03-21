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

namespace App\Domain\Catalog\Product\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Infrastructure\Abstract\IService;

final class DomainApiProductQueryService extends IService
{
    public function __construct(
        public readonly ProductRepository $repository,
        private readonly ProductSnapshotInterface $productSnapshotService
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function pageForList(array $filters, int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->pageForApiList($filters, $page, $pageSize);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function findById(mixed $id): ?array
    {
        return $this->productSnapshotService->getProduct($id, ['skus', 'attributes', 'gallery']);
    }
}