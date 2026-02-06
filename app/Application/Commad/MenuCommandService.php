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

namespace App\Application\Commad;

use App\Domain\Permission\Entity\MenuEntity;
use App\Domain\Permission\Service\MenuService;
use App\Infrastructure\Model\Permission\Menu;
use Hyperf\DbConnection\Db;

final class MenuCommandService
{
    public function __construct(private readonly MenuService $menuService) {}

    public function create(MenuEntity $entity): Menu
    {
        return Db::transaction(fn () => $this->menuService->create($entity));
    }

    public function update(int $id, MenuEntity $entity): bool
    {
        return Db::transaction(fn () => $this->menuService->update($id, $entity));
    }

    /**
     * @param \App\Domain\Permission\Contract\Common\DeleteInput $input
     */
    public function delete(\App\Domain\Permission\Contract\Common\DeleteInput $input): int
    {
        return $this->menuService->delete($input->getIds());
    }
}
