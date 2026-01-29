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

use App\Domain\Permission\Enum\DataPermission\PolicyType;

/**
 * 数据权限策略值对象.
 */
final class DataPolicy
{
    private int $id = 0;

    private PolicyType $type = PolicyType::Self;

    /**
     * @var array<int|string, mixed>
     */
    private array $value = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): PolicyType
    {
        return $this->type;
    }

    public function setType(PolicyType $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @param array<int|string, mixed> $value
     */
    public function setValue(array $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id ?: null,
            'policy_type' => $this->type->value,
            'value' => $this->value,
        ], static fn ($value) => $value !== null);
    }
}
