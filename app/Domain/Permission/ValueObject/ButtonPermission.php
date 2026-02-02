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
    public function __construct(
        private readonly int $id,
        private readonly string $code,
        private readonly string $title,
        private readonly ?string $i18n = null
    ) {
        if (trim($code) === '') {
            throw new \DomainException('按钮权限编码不能为空');
        }
        if (trim($title) === '') {
            throw new \DomainException('按钮权限名称不能为空');
        }
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            (int) ($payload['id'] ?? 0),
            (string) ($payload['code'] ?? ''),
            (string) ($payload['title'] ?? ''),
            isset($payload['i18n']) ? (string) $payload['i18n'] : null
        );
    }

    public function withId(int $id): self
    {
        return new self($id, $this->code, $this->title, $this->i18n);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function i18n(): ?string
    {
        return $this->i18n;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id ?: null,
            'code' => $this->code,
            'title' => $this->title,
            'i18n' => $this->i18n,
        ], static fn ($value) => $value !== null);
    }
}
