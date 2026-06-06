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

    public function testNoticeBarSchemaPasses(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_notice',
            'type' => 'notice-bar',
            'name' => '公告栏',
            'enabled' => true,
            'props' => ['speed' => 40],
            'style' => [],
            'data' => [
                'items' => [
                    ['text' => '新人下单立减', 'link' => ['type' => 'coupon', 'value' => '1']],
                ],
            ],
        ]);

        $vo = DiyPageSchemaVo::fromArray($schema, 'home');

        self::assertSame('notice-bar', $vo->toArray()['components'][0]['type']);
    }

    public function testCouponGroupLimitCannotExceedTen(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_coupon',
            'type' => 'coupon-group',
            'enabled' => true,
            'props' => ['limit' => 11],
            'style' => [],
            'data' => ['couponIds' => range(1, 11)],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('优惠券组最多');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testSeckillGroupRequiresActivityId(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_seckill',
            'type' => 'seckill-group',
            'enabled' => true,
            'props' => ['limit' => 6],
            'style' => [],
            'data' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('秒杀组件必须选择活动');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testGroupBuyGroupRequiresActivityId(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_group_buy',
            'type' => 'group-buy-group',
            'enabled' => true,
            'props' => ['limit' => 6],
            'style' => [],
            'data' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('拼团组件必须选择活动');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testProductRankRejectsInvalidRankType(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_rank',
            'type' => 'product-rank',
            'enabled' => true,
            'props' => ['rankType' => 'invalid'],
            'style' => [],
            'data' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('商品榜单类型无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testSearchBarPlaceholderCannotBeTooLong(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_search',
            'type' => 'search-bar',
            'enabled' => true,
            'props' => ['placeholder' => str_repeat('搜', 31)],
            'style' => [],
            'data' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('搜索框占位文案最多');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testRichTextRemovesUnsafeTags(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_rich_text',
            'type' => 'rich-text',
            'enabled' => true,
            'props' => [],
            'style' => [],
            'data' => ['content' => '<p>正文</p><script>alert(1)</script><iframe src="x"></iframe>'],
        ]);

        $vo = DiyPageSchemaVo::fromArray($schema, 'home');

        self::assertSame('<p>正文</p>', $vo->toArray()['components'][0]['data']['content']);
    }

    public function testImageCubeRejectsUnsupportedLayout(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_cube',
            'type' => 'image-cube',
            'enabled' => true,
            'props' => ['layout' => 'free'],
            'style' => [],
            'data' => ['items' => []],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('图片魔方布局无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testImageComponentRejectsUnsupportedWidthMode(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_image_ad',
            'type' => 'image-ad',
            'enabled' => true,
            'props' => ['widthMode' => 'free'],
            'style' => [],
            'data' => ['items' => []],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('图片组件宽度模式无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testImageComponentRejectsInvalidCustomWidth(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_banner',
            'type' => 'banner',
            'enabled' => true,
            'props' => ['widthMode' => 'custom', 'widthUnit' => 'percent', 'width' => 120],
            'style' => [],
            'data' => ['items' => []],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('图片组件百分比宽度需在');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testImageAdRejectsUnsupportedLayout(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_image_ad',
            'type' => 'image-ad',
            'enabled' => true,
            'props' => ['layout' => 'masonry'],
            'style' => [],
            'data' => ['items' => []],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('图片广告布局无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testImageComponentAcceptsControlledSizeProps(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_image_ad',
            'type' => 'image-ad',
            'enabled' => true,
            'props' => [
                'layout' => 'horizontal',
                'widthMode' => 'custom',
                'widthUnit' => 'percent',
                'width' => 88,
                'height' => 120,
                'radius' => 12,
                'objectFit' => 'contain',
            ],
            'style' => [],
            'data' => ['items' => []],
        ]);

        $vo = DiyPageSchemaVo::fromArray($schema, 'home');

        self::assertSame('horizontal', $vo->toArray()['components'][0]['props']['layout']);
    }

    public function testPageThemeSchemaPasses(): void
    {
        $schema = $this->validSchema();
        $schema['page']['theme'] = [
            'primaryColor' => '#2563eb',
            'priceColor' => '#ef4444',
            'backgroundColor' => '#f8fafc',
            'cardRadius' => 12,
            'buttonShape' => 'round',
        ];

        $vo = DiyPageSchemaVo::fromArray($schema, 'home');

        self::assertSame('#2563eb', $vo->toArray()['page']['theme']['primaryColor']);
    }

    public function testPageThemeRejectsInvalidColor(): void
    {
        $schema = $this->validSchema();
        $schema['page']['theme'] = [
            'primaryColor' => 'blue',
        ];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('装修主题颜色无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testPageThemeRejectsInvalidButtonShape(): void
    {
        $schema = $this->validSchema();
        $schema['page']['theme'] = [
            'buttonShape' => 'pill',
        ];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('装修按钮样式无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testProductGroupRejectsUnsupportedSource(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_products',
            'type' => 'product-group',
            'enabled' => true,
            'props' => ['source' => 'brand'],
            'style' => [],
            'data' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('商品组来源无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testProductGroupRejectsUnsupportedSort(): void
    {
        $schema = $this->schemaWithComponent([
            'id' => 'cmp_products',
            'type' => 'product-group',
            'enabled' => true,
            'props' => ['source' => 'recommend', 'sort' => 'random'],
            'style' => [],
            'data' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('商品组排序无效');

        DiyPageSchemaVo::fromArray($schema, 'home');
    }

    public function testOldSchemaVersionMigratesToCurrentVersion(): void
    {
        $schema = $this->validSchema();
        $schema['version'] = 0;

        $vo = DiyPageSchemaVo::fromArray($schema, 'home');

        self::assertSame(1, $vo->toArray()['version']);
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

    private function schemaWithComponent(array $component): array
    {
        $schema = $this->validSchema();
        $schema['components'] = [$component];

        return $schema;
    }
}
