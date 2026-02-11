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

namespace HyperfTests\Feature\Admin\Seckill;

use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * @internal
 * @coversNothing
 */
final class SeckillProductControllerTest extends ControllerCase
{
    private SeckillActivity $activity;

    private SeckillSession $session;

    private Product $product;

    private ProductSku $sku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity = SeckillActivity::create([
            'title' => '测试活动' . Str::random(5),
            'description' => '测试描述',
            'status' => 'pending',
            'is_enabled' => true,
        ]);

        $this->session = SeckillSession::create([
            'activity_id' => $this->activity->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 1,
        ]);

        $this->product = Product::create([
            'name' => '测试商品' . Str::random(5),
            'product_code' => 'TEST' . Str::random(8),
            'status' => 'active',
            'min_price' => 100,
            'max_price' => 100,
        ]);

        $this->sku = ProductSku::create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU' . Str::random(8),
            'sku_name' => '默认规格',
            'sale_price' => 100,
            'stock' => 100,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        SeckillProduct::truncate();
        SeckillSession::truncate();
        SeckillActivity::truncate();
        ProductSku::where('product_id', $this->product->id)->delete();
        $this->product->forceDelete();
        parent::tearDown();
    }

    public function testPageList(): void
    {
        $result = $this->get('/admin/seckill/product/list');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $result = $this->get('/admin/seckill/product/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);

        $this->forAddPermission('seckill:product:list');
        $result = $this->get('/admin/seckill/product/list', ['session_id' => $this->session->id], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('list', $result['data']);
        self::assertArrayHasKey('total', $result['data']);
    }

    public function testCreate(): void
    {
        $result = $this->post('/admin/seckill/product');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:product:create');

        // Test validation
        $result = $this->post('/admin/seckill/product', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);

        // Test successful creation
        $fill = [
            'activity_id' => $this->activity->id,
            'session_id' => $this->session->id,
            'product_id' => $this->product->id,
            'product_sku_id' => $this->sku->id,
            'original_price' => 100,
            'seckill_price' => 50,
            'quantity' => 10,
            'max_quantity_per_user' => 1,
            'is_enabled' => true,
        ];
        $result = $this->post('/admin/seckill/product', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('id', $result['data']);

        // Verify in database
        $entity = SeckillProduct::find($result['data']['id']);
        self::assertNotNull($entity);
        self::assertEquals(50, $entity->seckill_price);
        self::assertEquals(10, $entity->quantity);
    }

    public function testShow(): void
    {
        $entity = SeckillProduct::create([
            'activity_id' => $this->activity->id,
            'session_id' => $this->session->id,
            'product_id' => $this->product->id,
            'product_sku_id' => $this->sku->id,
            'original_price' => 100,
            'seckill_price' => 50,
            'quantity' => 10,
            'is_enabled' => true,
        ]);

        $result = $this->get('/admin/seckill/product/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:product:read');
        $result = $this->get('/admin/seckill/product/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertSame((int) $entity->id, (int) $result['data']['id']);
    }

    public function testUpdate(): void
    {
        $entity = SeckillProduct::create([
            'activity_id' => $this->activity->id,
            'session_id' => $this->session->id,
            'product_id' => $this->product->id,
            'product_sku_id' => $this->sku->id,
            'original_price' => 100,
            'seckill_price' => 50,
            'quantity' => 10,
            'is_enabled' => true,
        ]);

        $result = $this->put('/admin/seckill/product/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:product:update');

        $fill = [
            'original_price' => 120,
            'seckill_price' => 60,
            'quantity' => 20,
            'is_enabled' => false,
        ];
        $result = $this->put('/admin/seckill/product/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify update
        $entity->refresh();
        self::assertEquals(60, $entity->seckill_price);
        self::assertEquals(20, $entity->quantity);
        self::assertFalse($entity->is_enabled);
    }

    public function testDelete(): void
    {
        $entity = SeckillProduct::create([
            'activity_id' => $this->activity->id,
            'session_id' => $this->session->id,
            'product_id' => $this->product->id,
            'product_sku_id' => $this->sku->id,
            'original_price' => 100,
            'seckill_price' => 50,
            'quantity' => 10,
            'is_enabled' => true,
        ]);

        $result = $this->delete('/admin/seckill/product/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:product:delete');
        $result = $this->delete('/admin/seckill/product/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify deleted
        self::assertNull(SeckillProduct::find($entity->id));
    }

    public function testToggleStatus(): void
    {
        $entity = SeckillProduct::create([
            'activity_id' => $this->activity->id,
            'session_id' => $this->session->id,
            'product_id' => $this->product->id,
            'product_sku_id' => $this->sku->id,
            'original_price' => 100,
            'seckill_price' => 50,
            'quantity' => 10,
            'is_enabled' => true,
        ]);

        $this->forAddPermission('seckill:product:update');
        $result = $this->put('/admin/seckill/product/' . $entity->id . '/toggle-status', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify toggled
        $entity->refresh();
        self::assertFalse($entity->is_enabled);
    }

    public function testBatchCreate(): void
    {
        $this->forAddPermission('seckill:product:create');

        // Create another SKU
        $sku2 = ProductSku::create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU' . Str::random(8),
            'sku_name' => '规格2',
            'sale_price' => 150,
            'stock' => 50,
            'status' => 'active',
        ]);

        $fill = [
            'activity_id' => $this->activity->id,
            'session_id' => $this->session->id,
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'product_sku_id' => $this->sku->id,
                    'original_price' => 100,
                    'seckill_price' => 50,
                    'quantity' => 10,
                ],
                [
                    'product_id' => $this->product->id,
                    'product_sku_id' => $sku2->id,
                    'original_price' => 150,
                    'seckill_price' => 75,
                    'quantity' => 5,
                ],
            ],
        ];
        $result = $this->post('/admin/seckill/product/batch', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertIsArray($result['data']);
        self::assertCount(2, $result['data']);

        $sku2->forceDelete();
    }
}
