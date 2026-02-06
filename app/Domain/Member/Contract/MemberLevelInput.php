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

namespace App\Domain\Member\Contract;

/**
 * 会员等级输入契约接口.
 */
interface MemberLevelInput
{
    public function getId(): int;

    public function getName(): string;

    public function getLevel(): int;

    public function getGrowthValueMin(): int;

    public function getGrowthValueMax(): ?int;

    public function getDiscountRate(): ?float;

    public function getPointRate(): ?float;

    /**
     * @return null|array<string, mixed>
     */
    public function getPrivileges(): ?array;

    public function getIcon(): ?string;

    public function getColor(): ?string;

    public function getStatus(): string;

    public function getSortOrder(): ?int;

    public function getDescription(): ?string;

    public function getOperatorId(): int;

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
