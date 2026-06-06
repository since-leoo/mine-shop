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

namespace App\Domain\Content\DiyPage\ValueObject;

final class DiyPageSchemaVo
{
    private const MAX_COMPONENTS = 50;

    private const THEME_COLOR_KEYS = [
        'primaryColor',
        'priceColor',
        'backgroundColor',
    ];

    private const BUTTON_SHAPES = [
        'round',
        'square',
        'plain',
    ];

    private function __construct(private readonly array $schema) {}

    public static function fromArray(array $schema, string $pageKey): self
    {
        $schema = DiyComponentRegistryVo::migrateSchema($schema);
        self::assertPage($schema, $pageKey);
        self::assertComponents($schema['components']);

        return new self(self::normalize($schema));
    }

    public function toArray(): array
    {
        return $this->schema;
    }

    public function publishedPayload(): array
    {
        $schema = $this->schema;
        $schema['components'] = array_values(array_filter(
            $schema['components'],
            static fn (array $component): bool => ($component['enabled'] ?? true) === true
        ));

        return $schema;
    }

    private static function assertPage(array $schema, string $pageKey): void
    {
        if (($schema['version'] ?? null) !== DiyComponentRegistryVo::CURRENT_SCHEMA_VERSION) {
            throw new \DomainException('装修页面结构版本无效');
        }

        if (! isset($schema['page']) || ! \is_array($schema['page'])) {
            throw new \DomainException('装修页面信息不能为空');
        }

        if (($schema['page']['key'] ?? '') !== $pageKey) {
            throw new \DomainException('装修页面键不一致');
        }

        if (! isset($schema['components']) || ! \is_array($schema['components'])) {
            throw new \DomainException('装修组件不能为空');
        }

        self::assertPageTheme($schema['page']);
    }

    private static function assertPageTheme(array $page): void
    {
        $theme = $page['theme'] ?? [];
        if ($theme === []) {
            return;
        }

        if (! \is_array($theme)) {
            throw new \DomainException('装修主题格式无效');
        }

        foreach (self::THEME_COLOR_KEYS as $key) {
            if (! isset($theme[$key]) || $theme[$key] === '') {
                continue;
            }

            if (! \is_string($theme[$key]) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $theme[$key])) {
                throw new \DomainException('装修主题颜色无效');
            }
        }

        if (isset($theme['cardRadius'])) {
            $cardRadius = (int) $theme['cardRadius'];
            if ($cardRadius < 0 || $cardRadius > 64) {
                throw new \DomainException('装修卡片圆角需在0-64之间');
            }
        }

        $buttonShape = (string) ($theme['buttonShape'] ?? 'round');
        if (! \in_array($buttonShape, self::BUTTON_SHAPES, true)) {
            throw new \DomainException('装修按钮样式无效');
        }
    }

    private static function assertComponents(array $components): void
    {
        if (\count($components) > self::MAX_COMPONENTS) {
            throw new \DomainException('装修组件最多50个');
        }

        $ids = [];
        foreach ($components as $component) {
            if (! \is_array($component)) {
                throw new \DomainException('装修组件格式无效');
            }

            $id = (string) ($component['id'] ?? '');
            if ($id === '') {
                throw new \DomainException('组件ID不能为空');
            }
            if (isset($ids[$id])) {
                throw new \DomainException('组件ID重复');
            }
            $ids[$id] = true;

            $type = (string) ($component['type'] ?? '');
            DiyComponentRegistryVo::assertSupportedComponent($type);
            DiyComponentRegistryVo::assertComponentData($type, $component);
        }
    }

    private static function normalize(array $schema): array
    {
        $schema['components'] = array_values(array_map(
            static fn (array $component): array => DiyComponentRegistryVo::normalizeComponent($component),
            $schema['components']
        ));

        return $schema;
    }
}
