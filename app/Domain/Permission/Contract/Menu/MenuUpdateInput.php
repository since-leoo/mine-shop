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

namespace App\Domain\Permission\Contract\Menu;

/**
 * 更新菜单操作输入契约.
 */
interface MenuUpdateInput
{
    public function getId(): int;

    public function getParentId(): int;

    public function getName(): string;

    public function getPath(): ?string;

    public function getComponent(): ?string;

    public function getRedirect(): ?string;

    public function getStatus(): int;

    public function getSort(): int;

    public function getRemark(): ?string;

    public function getMeta(): array;

    public function getButtonPermissions(): array;

    public function getOperatorId(): int;
}
