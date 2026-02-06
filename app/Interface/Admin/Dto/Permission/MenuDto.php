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

namespace App\Interface\Admin\DTO\Permission;

use App\Domain\Permission\Contract\Menu\MenuCreateInput;
use App\Domain\Permission\Contract\Menu\MenuUpdateInput;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 菜单操作 DTO（创建和更新共用）.
 */
#[Valid]
class MenuDto implements MenuCreateInput, MenuUpdateInput
{
    public ?int $id = null;

    public int $parent_id = 0;

    #[Required]
    public string $name = '';

    public ?string $path = null;

    public ?string $component = null;

    public ?string $redirect = null;

    public int $status = 1;

    public int $sort = 0;

    public ?string $remark = null;

    public array $meta = [];

    public array $btnPermission = [];

    #[Required]
    public int $operator_id = 0;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getButtonPermissions(): array
    {
        return $this->btnPermission;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
