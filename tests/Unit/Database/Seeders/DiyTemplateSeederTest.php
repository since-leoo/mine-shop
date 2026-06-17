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

namespace HyperfTests\Unit\Database\Seeders;

use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 * @coversNothing
 */
final class DiyTemplateSeederTest extends TestCase
{
    public function testDefaultTemplateSchemasPassDomainValidation(): void
    {
        require_once BASE_PATH . '/databases/seeders/diy_template_seeder_20260606.php';

        $seeder = new \DiyTemplateSeeder20260606();
        $method = (new ReflectionClass($seeder))->getMethod('templates');
        $method->setAccessible(true);

        foreach ($method->invoke($seeder) as $template) {
            $vo = DiyPageSchemaVo::fromArray($template['schema'], $template['page_key']);

            self::assertSame($template['page_key'], $vo->toArray()['page']['key']);
        }
    }

    public function testCategoryTemplateOnlyContainsFullHeightCategoryPanel(): void
    {
        require_once BASE_PATH . '/databases/seeders/diy_template_seeder_20260606.php';

        $seeder = new \DiyTemplateSeeder20260606();
        $method = (new ReflectionClass($seeder))->getMethod('templates');
        $method->setAccessible(true);

        $categoryTemplate = null;
        foreach ($method->invoke($seeder) as $template) {
            if (($template['page_key'] ?? '') === 'category_home') {
                $categoryTemplate = $template;
                break;
            }
        }

        self::assertNotNull($categoryTemplate);
        self::assertSame(['category-panel'], array_column($categoryTemplate['schema']['components'], 'type'));
        self::assertSame('100%', $categoryTemplate['schema']['components'][0]['style']['height'] ?? null);
    }

    public function testDefaultCategoryPageOnlyContainsFullHeightCategoryPanel(): void
    {
        require_once BASE_PATH . '/databases/seeders/support/DefaultDiyPageSchemas.php';

        $categoryPages = array_values(array_filter(
            \DefaultDiyPageSchemas::all(),
            static fn (array $page): bool => ($page['page_key'] ?? '') === 'category'
        ));

        self::assertNotEmpty($categoryPages);
        foreach ($categoryPages as $page) {
            self::assertSame(['category-panel'], array_column($page['schema']['components'], 'type'));
            self::assertSame('100%', $page['schema']['components'][0]['style']['height'] ?? null);
        }
    }

    public function testDefaultSubPagesFollowDesignDraftStructure(): void
    {
        require_once BASE_PATH . '/databases/seeders/support/DefaultDiyPageSchemas.php';

        $pages = [];
        foreach (\DefaultDiyPageSchemas::all() as $page) {
            $pages[$page['page_key']][] = $page;
        }

        foreach (['cart', 'usercenter', 'coupon-center'] as $pageKey) {
            self::assertArrayHasKey($pageKey, $pages);
        }

        foreach ($pages['cart'] as $page) {
            self::assertSame(
                ['title-bar', 'rich-text', 'quick-nav', 'title-bar', 'product-group'],
                array_column($page['schema']['components'], 'type')
            );
            self::assertSame('#FFFFFF', $page['schema']['components'][1]['style']['backgroundColor'] ?? null);
            self::assertSame(24, $page['schema']['components'][1]['style']['borderRadius'] ?? null);
        }

        foreach ($pages['usercenter'] as $page) {
            self::assertSame(
                ['user-profile-header', 'user-stats', 'user-order-panel', 'user-menu-list', 'user-menu-list'],
                array_column($page['schema']['components'], 'type')
            );
            self::assertTrue($page['schema']['page']['style']['topTransparent'] ?? false);
            self::assertSame(['bottomLeft' => 30, 'bottomRight' => 30], $page['schema']['components'][0]['style']['borderRadius'] ?? null);
            self::assertSame(-20, $page['schema']['components'][1]['style']['margin']['top'] ?? null);
            self::assertSame(16, $page['schema']['components'][1]['style']['borderRadius'] ?? null);
            foreach (array_slice($page['schema']['components'], 1) as $component) {
                self::assertSame(20, $component['style']['margin']['right'] ?? null);
                self::assertSame(20, $component['style']['margin']['left'] ?? null);
            }
            self::assertSame('小花花', $page['schema']['components'][0]['props']['nickname'] ?? null);
            self::assertSame(['优惠券', '积分', '余额', '收藏'], array_column($page['schema']['components'][1]['data']['items'] ?? [], 'label'));
            self::assertSame(['待付款', '待发货', '待收货', '待评价', '退换/售后'], array_column($page['schema']['components'][2]['data']['items'] ?? [], 'label'));
            self::assertSame(['收货地址', '优惠券', '我的钱包'], array_column($page['schema']['components'][3]['data']['items'] ?? [], 'label'));
            self::assertSame(['联系客服', '设置'], array_column($page['schema']['components'][4]['data']['items'] ?? [], 'label'));
        }

        foreach ($pages['coupon-center'] as $page) {
            self::assertSame(
                ['image-ad', 'coupon-group', 'title-bar', 'product-group'],
                array_column($page['schema']['components'], 'type')
            );
            self::assertSame('coupon-hero', $page['schema']['components'][0]['props']['variant'] ?? null);
            self::assertSame('two-column', $page['schema']['components'][1]['props']['layout'] ?? null);
        }
    }
}
