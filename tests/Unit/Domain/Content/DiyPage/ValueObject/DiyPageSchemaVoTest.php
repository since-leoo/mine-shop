<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Content\DiyPage\ValueObject;

use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DiyPageSchemaVoTest extends TestCase
{
    public function testValidMinimalHomeSchemaPasses(): void
    {
        $vo = DiyPageSchemaVo::fromArray($this->validSchema(), 'home');

        self::assertSame('home', $vo->toArray()['page']['key']);
        self::assertCount(1, $vo->toArray()['components']);
    }

    public function testUnknownComponentThrows(): void
    {
        $schema = $this->validSchema();
        $schema['components'][0]['type'] = 'unknown';

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('不支持的装修组件');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testDuplicateComponentIdThrows(): void
    {
        $schema = $this->validSchema();
        $schema['components'][] = $schema['components'][0];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('组件ID重复');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testTooManyComponentsThrows(): void
    {
        $schema = $this->validSchema();
        $schema['components'] = [];
        for ($i = 1; $i <= 51; ++$i) {
            $schema['components'][] = [
                'id' => 'cmp_' . $i,
                'type' => 'gap',
                'enabled' => true,
                'props' => ['height' => 12],
                'style' => [],
                'data' => [],
            ];
        }

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('装修组件最多');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testPublishedPayloadRemovesDisabledComponents(): void
    {
        $schema = $this->validSchema();
        $schema['components'][] = [
            'id' => 'cmp_disabled',
            'type' => 'divider',
            'enabled' => false,
            'props' => [],
            'style' => [],
            'data' => [],
        ];

        $payload = DiyPageSchemaVo::fromArray($schema, 'home')->publishedPayload();

        self::assertSame(['cmp_banner'], array_column($payload['components'], 'id'));
    }

    public function testProductGroupLimitCannotExceedFifty(): void
    {
        $schema = $this->validSchema();
        $schema['components'][0] = [
            'id' => 'cmp_products',
            'type' => 'product-group',
            'enabled' => true,
            'props' => ['title' => '精选推荐', 'limit' => 51],
            'style' => [],
            'data' => ['source' => 'recommend', 'productIds' => []],
        ];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('商品组数量最多');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    private function validSchema(): array
    {
        return [
            'version' => 1,
            'page' => [
                'key' => 'home',
                'title' => '首页',
                'backgroundColor' => '#f7f8fa',
            ],
            'components' => [
                [
                    'id' => 'cmp_banner',
                    'type' => 'banner',
                    'name' => '轮播图',
                    'enabled' => true,
                    'props' => ['height' => 160],
                    'style' => [],
                    'data' => [
                        'items' => [
                            [
                                'image' => 'https://example.com/banner.png',
                                'title' => '春季上新',
                                'link' => ['type' => 'page', 'value' => '/pages/goods/list/index'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
