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

namespace App\Domain\Product\Api;

use App\Domain\Product\Service\CategoryService;
use Hyperf\Database\Model\Collection;

final class CategoryReadService
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function tree(int $parentId = 0): Collection
    {
        return $this->categoryService->getTree($parentId);
    }
}
