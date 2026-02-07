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

namespace App\Domain\Catalog\Category\Api\Query;

use App\Domain\Catalog\Category\Repository\CategoryRepository;
use App\Infrastructure\Abstract\IService;
use Hyperf\Database\Model\Collection;

/**
 * 面向 API 场景的分类查询领域服务.
 *
 * 继承 IService 获得 page()、count() 等基类方法.
 */
final class DomainApiCategoryQueryService extends IService
{
    public function __construct(public readonly CategoryRepository $repository) {}

    /**
     * 获取分类树.
     */
    public function tree(int $parentId = 0): Collection
    {
        return $this->repository->getTree($parentId);
    }
}
