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

namespace App\Domain\Permission\Contract\User;

use App\Domain\Auth\Enum\Status;
use App\Domain\Auth\Enum\Type;

/**
 * 输入契约：新增用户所需的数据。
 */
interface UserInput
{
    // 统一契约：当 getId() 为 0 视为新增，>0 视为更新；getUpdatedBy() 仅在更新场景使用
    public function getId(): int;

    public function getUpdatedBy(): int;

    public function getUsername(): string;

    public function getPassword(): string;

    public function getNickname(): string;

    public function getUserType(): Type;

    public function getPhone(): ?string;

    public function getEmail(): ?string;

    public function getAvatar(): ?string;

    public function getSigned(): ?string;

    public function getRemark(): ?string;

    public function getDepartmentIds(): array;

    public function getPositionIds(): array;

    public function getStatus(): Status;

    public function getBackendSetting(): array;

    public function getCreatedBy(): int;

    /**
     * 可选的数据权限策略载荷：缺省表示不变，空数组表示清空策略。
     *
     * @return null|array<string, mixed>
     */
    public function getPolicy(): ?array;
}
