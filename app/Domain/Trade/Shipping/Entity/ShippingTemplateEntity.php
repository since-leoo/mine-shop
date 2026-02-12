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

namespace App\Domain\Trade\Shipping\Entity;

use App\Domain\Trade\Shipping\Contract\ShippingTemplateInput;

/**
 * 运费模板实体.
 */
final class ShippingTemplateEntity
{
    private int $id;

    private ?string $name;

    private ?string $chargeType;

    /** @var null|array<int, array<string, mixed>> */
    private ?array $rules;

    /** @var null|array<int, array<string, mixed>> */
    private ?array $freeRules;

    private ?bool $isDefault;

    private ?string $status;

    public function __construct()
    {
        $this->id = 0;
        $this->name = null;
        $this->chargeType = null;
        $this->rules = null;
        $this->freeRules = null;
        $this->isDefault = false;
        $this->status = 'active';
    }

    /**
     * 创建行为方法：接收 DTO，设置所有字段.
     */
    public function create(ShippingTemplateInput $dto): self
    {
        $this->name = $dto->getName();
        $this->chargeType = $dto->getChargeType();
        $this->rules = $dto->getRules();
        $this->freeRules = $dto->getFreeRules();
        $this->isDefault = $dto->getIsDefault() ?? false;
        $this->status = $dto->getStatus() ?? 'active';

        return $this;
    }

    /**
     * 更新行为方法：接收 DTO，条件更新非 null 字段.
     */
    public function update(ShippingTemplateInput $dto): self
    {
        if ($dto->getName() !== null) {
            $this->name = $dto->getName();
        }

        if ($dto->getChargeType() !== null) {
            $this->chargeType = $dto->getChargeType();
        }

        if ($dto->getRules() !== null) {
            $this->rules = $dto->getRules();
        }

        if ($dto->getFreeRules() !== null) {
            $this->freeRules = $dto->getFreeRules();
        }

        if ($dto->getIsDefault() !== null) {
            $this->isDefault = $dto->getIsDefault();
        }

        if ($dto->getStatus() !== null) {
            $this->status = $dto->getStatus();
        }

        return $this;
    }

    /**
     * 转换为数组，snake_case 输出用于持久化.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'charge_type' => $this->chargeType,
            'rules' => $this->rules,
            'free_rules' => $this->freeRules,
            'is_default' => $this->isDefault,
            'status' => $this->status,
        ], static fn ($value) => $value !== null);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getChargeType(): ?string
    {
        return $this->chargeType;
    }

    public function setChargeType(?string $chargeType): self
    {
        $this->chargeType = $chargeType;
        return $this;
    }

    /**
     * @return null|array<int, array<string, mixed>>
     */
    public function getRules(): ?array
    {
        return $this->rules;
    }

    /**
     * @param null|array<int, array<string, mixed>> $rules
     */
    public function setRules(?array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * @return null|array<int, array<string, mixed>>
     */
    public function getFreeRules(): ?array
    {
        return $this->freeRules;
    }

    /**
     * @param null|array<int, array<string, mixed>> $freeRules
     */
    public function setFreeRules(?array $freeRules): self
    {
        $this->freeRules = $freeRules;
        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
