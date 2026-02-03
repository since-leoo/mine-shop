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

use App\Domain\Product\Api\CategoryReadService;

final class CategoryQueryApiService
{
    public function __construct(
        private readonly CategoryReadService $readService,
        private readonly CategoryTransformer $transformer
    ) {}

    public function tree(int $parentId = 0): array
    {
        $collection = $this->readService->tree($parentId);
        return $this->transformer->transformTree($collection);
    }
}
