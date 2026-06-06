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

namespace App\Domain\Content\DiyPage\Contract;

interface DiyTemplateInput
{
    public function getCategoryId(): int;

    public function getName(): string;

    public function getPageKey(): string;

    public function getPageType(): string;

    public function getCover(): ?string;

    public function getDescription(): ?string;

    public function getSchema(): array;

    public function getSort(): int;

    public function isEnabled(): bool;
}
