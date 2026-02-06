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

namespace App\Application\Query;

use App\Domain\Product\Enum\BrandStatus;
use App\Domain\Product\Service\BrandService;
use App\Infrastructure\Model\Product\Brand;
use Hyperf\Cache\Annotation\Cacheable;

/**
 * 品牌查询服务：处理所有读操作.
 */
final class BrandQueryService
{
    public function __construct(private readonly BrandService $brandService) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->brandService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?Brand
    {
        return $this->brandService->findById($id);
    }

    public function options(): array
    {
        return $this->brandService->getOptions();
    }

    #[Cacheable(prefix: 'brands:statistics', ttl: 1800)]
    public function statistics(): array
    {
        return [
            'total' => Brand::count(),
            'active' => Brand::where('status', BrandStatus::ACTIVE->value)->count(),
            'inactive' => Brand::where('status', BrandStatus::INACTIVE->value)->count(),
        ];
    }
}
