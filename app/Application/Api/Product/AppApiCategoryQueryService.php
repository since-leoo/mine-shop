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

namespace App\Application\Api\Product;

use App\Domain\Catalog\Category\Api\Query\DomainApiCategoryQueryService;
use Hyperf\Database\Model\Collection;

final class AppApiCategoryQueryService
{
    public function __construct(
        private readonly DomainApiCategoryQueryService $categoryQueryService
    ) {}

    /**
     * 返回分类树 Collection（Category Model）.
     */
    public function tree(int $parentId = 0): Collection
    {
        return $this->categoryQueryService->tree($parentId);
    }
}
