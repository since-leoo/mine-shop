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

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Domain\Permission\Contract\Menu\MenuCreateInput;
use App\Domain\Permission\Contract\Menu\MenuUpdateInput;
use App\Domain\Permission\Mapper\MenuMapper;
use App\Domain\Permission\Service\DomainMenuService;
use App\Infrastructure\Model\Permission\Menu;
use Hyperf\DbConnection\Db;

final class AppMenuCommandService
{
    public function __construct(private readonly DomainMenuService $menuService) {}

    public function create(MenuCreateInput $input): Menu
    {
        // 通过 Mapper 获取新实体
        $entity = MenuMapper::getNewEntity();
        $this->fillEntityFromInput($entity, $input);
        $entity->setCreatedBy($input->getOperatorId());

        return Db::transaction(fn () => $this->menuService->create($entity));
    }

    public function update(MenuUpdateInput $input): bool
    {
        // 通过 Mapper 获取新实体（MenuService 内部会获取现有数据）
        $entity = MenuMapper::getNewEntity();
        $entity->setId($input->getId());
        $this->fillEntityFromInput($entity, $input);
        $entity->setUpdatedBy($input->getOperatorId());

        return Db::transaction(fn () => $this->menuService->update($input->getId(), $entity));
    }

    public function delete(DeleteInput $input): int
    {
        return $this->menuService->delete($input->getIds());
    }

    /**
     * 从 Input 填充 Entity.
     * @param mixed $entity
     */
    private function fillEntityFromInput($entity, MenuCreateInput|MenuUpdateInput $input): void
    {
        $entity->setParentId($input->getParentId());
        $entity->setName($input->getName());
        $entity->setPath($input->getPath());
        $entity->setComponent($input->getComponent());
        $entity->setRedirect($input->getRedirect());
        $entity->setStatus(Status::tryFrom($input->getStatus()) ?? Status::Normal);
        $entity->setSort($input->getSort());
        $entity->setRemark($input->getRemark());
        $entity->setMeta($input->getMeta());
        $entity->setButtonPermissions($input->getButtonPermissions());
    }
}
