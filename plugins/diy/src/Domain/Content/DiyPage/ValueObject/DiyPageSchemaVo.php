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

namespace Plugin\Diy\Domain\Content\DiyPage\ValueObject;

use App\Infrastructure\Error\DiyError;

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
            throw DiyError::SCHEMA_VERSION_INVALID->exception();
        }

        if (! isset($schema['page']) || ! \is_array($schema['page'])) {
            throw DiyError::PAGE_INFORMATION_REQUIRED->exception();
        }

        if (($schema['page']['key'] ?? '') !== $pageKey) {
            throw DiyError::PAGE_KEY_MISMATCH->exception();
        }

        if (! isset($schema['components']) || ! \is_array($schema['components'])) {
            throw DiyError::COMPONENTS_REQUIRED->exception();
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
            throw DiyError::THEME_FORMAT_INVALID->exception();
        }

        foreach (self::THEME_COLOR_KEYS as $key) {
            if (! isset($theme[$key]) || $theme[$key] === '') {
                continue;
            }

            if (! \is_string($theme[$key]) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $theme[$key])) {
                throw DiyError::THEME_COLOR_INVALID->exception();
            }
        }

        if (isset($theme['cardRadius'])) {
            $cardRadius = (int) $theme['cardRadius'];
            if ($cardRadius < 0 || $cardRadius > 64) {
                throw DiyError::CARD_RADIUS_INVALID->exception();
            }
        }

        $buttonShape = (string) ($theme['buttonShape'] ?? 'round');
        if (! \in_array($buttonShape, self::BUTTON_SHAPES, true)) {
            throw DiyError::BUTTON_STYLE_INVALID->exception();
        }
    }

    private static function assertComponents(array $components): void
    {
        if (\count($components) > self::MAX_COMPONENTS) {
            throw DiyError::COMPONENT_LIMIT_EXCEEDED->exception();
        }

        $ids = [];
        foreach ($components as $component) {
            if (! \is_array($component)) {
                throw DiyError::COMPONENT_FORMAT_INVALID->exception();
            }

            $id = (string) ($component['id'] ?? '');
            if ($id === '') {
                throw DiyError::COMPONENT_ID_REQUIRED->exception();
            }
            if (isset($ids[$id])) {
                throw DiyError::COMPONENT_ID_DUPLICATE->exception();
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
