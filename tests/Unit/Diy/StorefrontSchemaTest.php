<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Diy;

use App\Domain\Infrastructure\Plugin\Contract\DiyCatalogDataSourceInterface;
use PHPUnit\Framework\TestCase;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyStorefrontService;
use Plugin\Diy\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use Plugin\Diy\Domain\Content\DiyPage\ValueObject\DiyPagePublishValidationVo;

final class StorefrontSchemaTest extends TestCase
{
    public function testFreshSchemasUseRegisteredRoutesAndRenderersAndNoExamplePurchases(): void
    {
        require_once BASE_PATH . '/plugins/diy/Database/Seeders/support/DefaultDiyPageSchemas.php';
        $app = json_decode(ltrim(file_get_contents(BASE_PATH . '/miniprogram/src/app.config.json'), "\xEF\xBB\xBF"), true);
        $renderer = file_get_contents(BASE_PATH . '/miniprogram/src/plugins/diy/components/diy-renderer/index.tsx');
        $schemas = \seeders\support\DefaultDiyPageSchemas::all();
        self::assertCount(10, $schemas);
        foreach ($schemas as $definition) {
            $schema = DiyPageSchemaVo::fromArray($definition['schema'], $definition['page_key'])->toArray();
            self::assertTrue(DiyPagePublishValidationVo::inspect($schema)->passed());
            foreach ($schema['components'] as $component) {
                self::assertStringContainsString($component['type'], $renderer);
                if ($component['type'] === 'product-group') {
                    self::assertSame([], $component['data']['products']);
                    self::assertSame([], $component['data']['product_ids']);
                }
                if ($component['type'] === 'user-stats') {
                    $stats = array_column($component['data']['items'], null, 'type');
                    self::assertSame('/pages/usercenter/wallet-transactions/index', $stats['points']['link']['path']);
                    self::assertSame(['type' => 'points'], $stats['points']['link']['params']);
                    self::assertSame('none', $stats['collect']['link']['type']);
                }
            }
            $walk = function (array $node) use (&$walk, $app): void {
                if (($node['type'] ?? '') === 'page') {
                    self::assertContains(ltrim(explode('?', $node['path'])[0], '/'), $app['pages']);
                }
                foreach ($node as $value) {
                    if (is_array($value)) $walk($value);
                }
            };
            $walk($schema);
            self::assertStringNotContainsString('02:18:45', json_encode($schema));
            self::assertStringNotContainsString('WARM2026', json_encode($schema));
        }
    }

    public function testSourceOverridesStaleModeAndCategoryUsesRealFilters(): void
    {
        $catalog = $this->createMock(DiyCatalogDataSourceInterface::class);
        $catalog->expects(self::once())->method('storefrontProducts')
            ->with(['status' => 'active', 'category_id' => 7, 'order_by' => 'min_price', 'order_by_direction' => 'asc'], 1, 4)
            ->willReturn(['list' => [['id' => 19, 'min_price' => 599]], 'total' => 1]);
        $result = (new DomainDiyStorefrontService($catalog))->resolve(['components' => [[
            'type' => 'product-group', 'props' => ['source' => 'category', 'mode' => 'recommend', 'categoryId' => 7, 'sort' => 'price_asc', 'limit' => 4],
            'data' => ['products' => [['id' => 999, 'min_price' => 1]]],
        ]]]);
        self::assertSame([['id' => 19, 'min_price' => 599]], $result['components'][0]['data']['products']);
    }

    public function testManualSelectionKeepsOrderAndUnsupportedSourcesDoNotShowSnapshot(): void
    {
        $catalog = $this->createMock(DiyCatalogDataSourceInterface::class);
        $catalog->expects(self::once())->method('storefrontProducts')->with(['status' => 'active', 'ids' => [8, 3]], 1, 10)
            ->willReturn(['list' => [['id' => 3], ['id' => 8]], 'total' => 2]);
        $result = (new DomainDiyStorefrontService($catalog))->resolve(['components' => [
            ['type' => 'product-group', 'props' => ['source' => 'manual'], 'data' => ['product_ids' => [8, 3]]],
            ['type' => 'product-group', 'props' => ['source' => 'tag'], 'data' => ['products' => [['id' => 99]]]],
            ['type' => 'product-group', 'enabled' => false, 'data' => []],
        ]]);
        self::assertSame([8, 3], array_column($result['components'][0]['data']['products'], 'id'));
        self::assertSame([], $result['components'][1]['data']['products']);
        self::assertSame('unsupported_source', $result['components'][1]['data']['unavailable_reason']);
    }

    public function testExpiredActivitiesAndSnapshotPricesAreNotPublished(): void
    {
        $catalog = $this->createMock(DiyCatalogDataSourceInterface::class);
        $catalog->method('groupBuys')->willReturn(['list' => [[
            'id' => 4, 'status' => 'active', 'is_enabled' => true,
            'start_time' => '2020-01-01', 'end_time' => '2020-02-01',
        ]]]);
        $catalog->method('seckills')->willReturn(['list' => []]);
        $result = (new DomainDiyStorefrontService($catalog))->resolve(['components' => [
            ['type' => 'group-buy-group', 'data' => ['groupBuyIds' => [4], 'activities' => [['id' => 4]]]],
            ['type' => 'seckill-group', 'data' => ['sessionId' => 3, 'products' => [['id' => 9, 'price' => 1]]]],
        ]]);
        self::assertSame([], $result['components'][0]['data']['activities']);
        self::assertNull($result['components'][1]['data']['session']);
        self::assertSame([], $result['components'][1]['data']['products']);
    }

    public function testActualEditorGroupBuySelectionPassesPublishValidation(): void
    {
        $schema = ['version' => 1, 'page' => ['key' => 'home'], 'components' => [[
            'id' => 'groups', 'type' => 'group-buy-group', 'props' => ['limit' => 4],
            'data' => ['groupBuyIds' => [3], 'activities' => [['id' => 3]]],
        ]]];
        $normalized = DiyPageSchemaVo::fromArray($schema, 'home')->toArray();
        self::assertTrue(DiyPagePublishValidationVo::inspect($normalized)->passed());
    }
}
