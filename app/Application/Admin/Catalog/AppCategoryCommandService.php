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

namespace App\Application\Admin\Catalog;

use App\Domain\Catalog\Category\Contract\CategoryInput;
use App\Domain\Catalog\Category\Service\DomainCategoryService;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\DbConnection\Db;

/**
 * 分类命令服务：处理所有写操作.
 */
final class AppCategoryCommandService
{
    public function __construct(
        private readonly DomainCategoryService $categoryService,
    ) {}

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function create(CategoryInput $input): Category
    {
        return Db::transaction(fn () => $this->categoryService->create($input));
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function update(CategoryInput $input): bool
    {
        return Db::transaction(fn () => $this->categoryService->update($input));
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function delete(int $id): bool
    {
        return Db::transaction(fn () => $this->categoryService->delete($id));
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function move(int $categoryId, int $newParentId): bool
    {
        return Db::transaction(fn () => $this->categoryService->move($categoryId, $newParentId));
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function updateSort(array $sortData): bool
    {
        return Db::transaction(fn () => $this->categoryService->updateSort($sortData));
    }
}
