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

namespace App\Application\Api\Geo;

use App\Domain\Infrastructure\Geo\Api\Query\DomainApiGeoRegionQueryService;

final class AppApiGeoRegionQueryService
{
    public function __construct(private readonly DomainApiGeoRegionQueryService $queryService) {}

    /**
     * @return array{version: null|string, updated_at: null|string, parent_code: null|string, list: array<int, array<string, mixed>>}
     */
    public function children(?string $parentCode, int $limit = 200): array
    {
        return $this->queryService->children($parentCode, $limit);
    }
}
