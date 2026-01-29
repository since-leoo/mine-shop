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

namespace App\Domain\Permission\ValueObject;

/**
 * 菜单按钮权限.
 */
final class ButtonPermission
{
    private int $id = 0;

    private string $code = '';

    private string $title = '';

    private ?string $i18n = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code = ''): self
    {
        $this->code = $code;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title = ''): self
    {
        $this->title = $title;
        return $this;
    }

    public function getI18n(): ?string
    {
        return $this->i18n;
    }

    public function setI18n(?string $i18n = null): self
    {
        $this->i18n = $i18n;
        return $this;
    }
}
