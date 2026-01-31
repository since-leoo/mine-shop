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

namespace App\Domain\SystemSetting\Entity;

use App\Infrastructure\Model\Setting\SystemSetting;

final class SystemSettingEntity
{
    private ?int $id = null;

    private string $key = '';

    private string $group = 'basic';

    private string $type = 'string';

    private string $label = '';

    private ?string $description = null;

    private int $sort = 0;

    private bool $isSensitive = false;

    /**
     * @var array<string, mixed>
     */
    private array $meta = [];

    private mixed $value = null;

    private ?string $rawValue = null;

    public static function fromModel(SystemSetting $model): self
    {
        $entity = new self();
        $entity->id = $model->id;
        $entity->key = $model->key;
        $entity->group = $model->group;
        $entity->type = $model->type;
        $entity->label = $model->label;
        $entity->description = $model->description;
        $entity->sort = $model->sort;
        $entity->isSensitive = (bool) $model->is_sensitive;
        $entity->meta = \is_array($model->meta) ? $model->meta : [];
        $entity->rawValue = $model->value;
        $entity->value = $entity->castValue($model->value);

        return $entity;
    }

    /**
     * @param array<string, mixed> $definition
     */
    public static function fromDefinition(string $group, string $key, array $definition): self
    {
        $entity = new self();
        $entity->group = $group;
        $entity->key = $key;
        $entity->type = (string) ($definition['type'] ?? 'string');
        $entity->label = (string) ($definition['label'] ?? $key);
        $entity->description = $definition['description'] ?? null;
        $entity->sort = (int) ($definition['sort'] ?? 0);
        $entity->isSensitive = (bool) ($definition['is_sensitive'] ?? false);
        $entity->meta = \is_array($definition['meta'] ?? null) ? $definition['meta'] : [];
        $entity->setValue($definition['default'] ?? null);

        return $entity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    public function isSensitive(): bool
    {
        return $this->isSensitive;
    }

    public function setSensitive(bool $sensitive): self
    {
        $this->isSensitive = $sensitive;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getRawValue(): ?string
    {
        return $this->rawValue;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $this->sanitizeValue($value);
        $this->rawValue = $this->formatValue($this->value);
        return $this;
    }

    public function withValue(mixed $value): self
    {
        $clone = clone $this;
        $clone->setValue($value);
        return $clone;
    }

    /**
     * 用于持久化的数组.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'group' => $this->group,
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'sort' => $this->sort,
            'is_sensitive' => $this->isSensitive,
            'meta' => $this->meta,
            'value' => $this->rawValue,
        ];
    }

    /**
     * API响应结构.
     *
     * @return array<string, mixed>
     */
    public function toResponse(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'group' => $this->group,
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'sort' => $this->sort,
            'is_sensitive' => $this->isSensitive,
            'meta' => $this->meta,
            'value' => $this->value,
        ];
    }

    private function castValue(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => $value === '1' || mb_strtolower($value) === 'true',
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => $this->normalizeJsonValue($value),
            default => $value,
        };
    }

    private function sanitizeValue(mixed $value): mixed
    {
        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => $value === null ? null : (int) $value,
            'decimal' => $value === null ? null : (float) $value,
            'json' => $this->normalizeJsonValue($value),
            default => $value === null ? null : (string) $value,
        };
    }

    private function formatValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'integer', 'decimal' => (string) $value,
            'json' => json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };
    }

    /**
     * @return array<mixed>
     */
    private function normalizeJsonValue(mixed $value): array
    {
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            return \is_array($decoded) ? $decoded : [];
        }

        return \is_array($value) ? $value : [];
    }
}
