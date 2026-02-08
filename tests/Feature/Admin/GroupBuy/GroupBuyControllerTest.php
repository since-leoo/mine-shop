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

namespace HyperfTests\Feature\Admin\GroupBuy;

use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuy;

/**
 * @internal
 * @coversNothing
 */
final class GroupBuyControllerTest extends ControllerCase
{
    private ?Product $product = null;

    private ?ProductSku $sku = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestProduct();
    }

    protected function tearDown(): void
    {
        GroupBuy::query()->forceDelete();
        if ($this->sku) {
            $this->sku->forceDelete();
        }
        if ($this->product) {
            $this->product->forceDelete();
        }
        parent::tearDown();
    }

    public function testPageList(): void
    {
        $result = $this->get('/admin/group-buy/list');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $result = $this->get('/admin/group-buy/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);

        $this->forAddPermission('promotion:group_buy:list');
        $result = $this->get('/admin/group-buy/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('list', $result['data']);
        self::assertArrayHasKey('total', $result['data']);

        $this->deletePermissions('promotion:group_buy:list');
        $result = $this->get('/admin/group-buy/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
    }

    public function testStats(): void
    {
        $this->forAddPermission('promotion:group_buy:list');
        $result = $this->get('/admin/group-buy/stats', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('total', $result['data']);
        self::assertArrayHasKey('enabled', $result['data']);
        self::assertArrayHasKey('disabled', $result['data']);
        self::assertArrayHasKey('active', $result['data']);
    }

    public function testCreate(): void
    {
        $result = $this->post('/admin/group-buy');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('promotion:group_buy:create');

        // Test validation
        $result = $this->post('/admin/group-buy', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);

        // Test successful creation
        $fill = [
            'title' => '测试团购活动' . Str::random(5),
            'description' => '这是一个测试团购活动',
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'original_price' => 100.00,
            'group_price' => 80.00,
            'min_people' => 2,
            'max_people' => 10,
            'start_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addDays(7)->format('Y-m-d H:i:s'),
            'group_time_limit' => 24,
            'status' => 'pending',
            'total_quantity' => 100,
            'is_enabled' => true,
        ];
        $result = $this->post('/admin/group-buy', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('id', $result['data']);
        self::assertSame($fill['title'], $result['data']['title']);

        // Verify in database
        $entity = GroupBuy::find($result['data']['id']);
        self::assertNotNull($entity);
        self::assertSame($fill['title'], $entity->title);
        self::assertSame($fill['status'], $entity->status);
        self::assertTrue($entity->is_enabled);
        self::assertEquals($fill['group_price'], $entity->group_price);
    }

    public function testShow(): void
    {
        $entity = GroupBuy::create([
            'title' => '测试团购' . Str::random(5),
            'description' => '测试描述',
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'original_price' => 100.00,
            'group_price' => 80.00,
            'min_people' => 2,
            'max_people' => 10,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addDays(7),
            'group_time_limit' => 24,
            'status' => 'pending',
            'total_quantity' => 100,
            'is_enabled' => true,
        ]);

        $result = $this->get('/admin/group-buy/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('promotion:group_buy:read');
        $result = $this->get('/admin/group-buy/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertSame($entity->title, $result['data']['title']);

        // Test non-existent
        $result = $this->get('/admin/group-buy/999999', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FAIL->value);
    }

    public function testUpdate(): void
    {
        $entity = GroupBuy::create([
            'title' => '测试团购' . Str::random(5),
            'description' => '测试描述',
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'original_price' => 100.00,
            'group_price' => 80.00,
            'min_people' => 2,
            'max_people' => 10,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addDays(7),
            'group_time_limit' => 24,
            'status' => 'pending',
            'total_quantity' => 100,
            'is_enabled' => true,
        ]);

        $result = $this->put('/admin/group-buy/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('promotion:group_buy:update');

        $fill = [
            'title' => '更新后的团购' . Str::random(5),
            'description' => '更新后的描述',
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'original_price' => 120.00,
            'group_price' => 90.00,
            'min_people' => 3,
            'max_people' => 15,
            'start_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addDays(14)->format('Y-m-d H:i:s'),
            'group_time_limit' => 48,
            'status' => 'active',
            'total_quantity' => 200,
            'is_enabled' => false,
        ];
        $result = $this->put('/admin/group-buy/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify update
        $entity->refresh();
        self::assertSame($fill['title'], $entity->title);
        self::assertSame($fill['status'], $entity->status);
        self::assertFalse($entity->is_enabled);
        self::assertEquals($fill['group_price'], $entity->group_price);
    }

    public function testDelete(): void
    {
        $entity = GroupBuy::create([
            'title' => '测试团购' . Str::random(5),
            'description' => '测试描述',
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'original_price' => 100.00,
            'group_price' => 80.00,
            'min_people' => 2,
            'max_people' => 10,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addDays(7),
            'group_time_limit' => 24,
            'status' => 'pending',
            'total_quantity' => 100,
            'sold_quantity' => 0,
            'is_enabled' => true,
        ]);

        $result = $this->delete('/admin/group-buy/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('promotion:group_buy:delete');
        $result = $this->delete('/admin/group-buy/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify soft deleted
        self::assertNull(GroupBuy::find($entity->id));
        self::assertNotNull(GroupBuy::withTrashed()->find($entity->id));
    }

    public function testToggleStatus(): void
    {
        $entity = GroupBuy::create([
            'title' => '测试团购' . Str::random(5),
            'description' => '测试描述',
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'original_price' => 100.00,
            'group_price' => 80.00,
            'min_people' => 2,
            'max_people' => 10,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addDays(7),
            'group_time_limit' => 24,
            'status' => 'pending',
            'total_quantity' => 100,
            'is_enabled' => true,
        ]);

        $this->forAddPermission('promotion:group_buy:update');
        $result = $this->put('/admin/group-buy/' . $entity->id . '/toggle-status', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify toggled
        $entity->refresh();
        self::assertFalse($entity->is_enabled);

        // Toggle back
        $result = $this->put('/admin/group-buy/' . $entity->id . '/toggle-status', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $entity->refresh();
        self::assertTrue($entity->is_enabled);
    }

    private function createTestProduct(): void
    {
        $this->product = Product::create([
            'name' => '测试商品' . Str::random(5),
            'category_id' => 1,
            'status' => 'active',
            'price' => 100.00,
        ]);
        $this->sku = ProductSku::create([
            'product_id' => $this->product->id,
            'name' => '默认规格',
            'price' => 100.00,
            'stock' => 1000,
        ]);
    }
}
