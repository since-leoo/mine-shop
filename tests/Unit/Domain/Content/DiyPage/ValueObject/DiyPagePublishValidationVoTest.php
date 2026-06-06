<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Content\DiyPage\ValueObject;

use App\Domain\Content\DiyPage\ValueObject\DiyPagePublishValidationVo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DiyPagePublishValidationVoTest extends TestCase
{
    public function testEmptyImageCreatesBlockingIssue(): void
    {
        $result = DiyPagePublishValidationVo::inspect($this->schemaWithComponent([
            'id' => 'cmp_banner',
            'type' => 'banner',
            'name' => '轮播图',
            'enabled' => true,
            'props' => [],
            'style' => [],
            'data' => [
                'items' => [
                    ['image' => '', 'link' => ['type' => 'page', 'path' => '/pages/home/index']],
                ],
            ],
        ]));

        self::assertFalse($result->passed());
        self::assertSame('image_required', $result->issues()[0]['code']);
    }

    public function testEmptyPageLinkCreatesBlockingIssue(): void
    {
        $result = DiyPagePublishValidationVo::inspect($this->schemaWithComponent([
            'id' => 'cmp_image',
            'type' => 'image-ad',
            'name' => '图片广告',
            'enabled' => true,
            'props' => [],
            'style' => [],
            'data' => [
                'items' => [
                    ['image' => 'https://example.com/a.png', 'link' => ['type' => 'page', 'path' => '']],
                ],
            ],
        ]));

        self::assertFalse($result->passed());
        self::assertSame('link_required', $result->issues()[0]['code']);
    }

    public function testManualProductGroupWithoutProductsCreatesBlockingIssue(): void
    {
        $result = DiyPagePublishValidationVo::inspect($this->schemaWithComponent([
            'id' => 'cmp_products',
            'type' => 'product-group',
            'name' => '商品组',
            'enabled' => true,
            'props' => ['source' => 'manual'],
            'style' => [],
            'data' => ['product_ids' => [], 'products' => []],
        ]));

        self::assertFalse($result->passed());
        self::assertSame('product_source_required', $result->issues()[0]['code']);
    }

    public function testMarketingGroupWithoutActivityCreatesBlockingIssue(): void
    {
        $result = DiyPagePublishValidationVo::inspect($this->schemaWithComponent([
            'id' => 'cmp_seckill',
            'type' => 'seckill-group',
            'name' => '秒杀组',
            'enabled' => true,
            'props' => [],
            'style' => [],
            'data' => [],
        ]));

        self::assertFalse($result->passed());
        self::assertSame('activity_required', $result->issues()[0]['code']);
    }

    public function testValidSchemaPasses(): void
    {
        $result = DiyPagePublishValidationVo::inspect($this->schemaWithComponent([
            'id' => 'cmp_products',
            'type' => 'product-group',
            'name' => '商品组',
            'enabled' => true,
            'props' => ['source' => 'recommend', 'limit' => 6],
            'style' => [],
            'data' => [],
        ]));

        self::assertTrue($result->passed());
        self::assertSame([], $result->issues());
    }

    private function schemaWithComponent(array $component): array
    {
        return [
            'version' => 1,
            'page' => ['key' => 'home', 'title' => '首页'],
            'components' => [$component],
        ];
    }
}
