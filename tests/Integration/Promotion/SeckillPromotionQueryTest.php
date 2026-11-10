<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Promotion;

use App\Application\Api\Seckill\AppApiSeckillProductQueryService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillProductDetailService;
use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillQueryService;
use App\Domain\Trade\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Infrastructure\Model\Seckill\SeckillSession;
use App\Interface\Api\Controller\V1\Seckill\SeckillProductController;
use App\Interface\Api\Request\V1\Seckill\SeckillPromotionRequest;
use App\Interface\Api\Transformer\ProductTransformer;
use App\Interface\Api\Transformer\Seckill\SeckillProductTransformer;
use Carbon\Carbon;
use HyperfTests\Support\DatabaseTestCase;

final class SeckillPromotionQueryTest extends DatabaseTestCase
{
    private SeckillProductController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2026_02_01_000001_create_mall_seckill_activities_table.php' => 'CreateMallSeckillActivitiesTable',
            '2026_02_01_000002_create_mall_seckill_sessions_table.php' => 'CreateMallSeckillSessionsTable',
            '2026_02_01_000003_create_mall_seckill_session_products_table.php' => 'CreateMallSeckillSessionProductsTable',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        foreach ([1 => true, 2 => true, 3 => false] as $id => $enabled) {
            $this->connection->table('seckill_activities')->insert(['id' => $id, 'title' => '活动' . $id, 'status' => 'active', 'is_enabled' => $enabled]);
        }
        foreach ([[11, 1, -60, 60, true, 'active'], [12, 1, 60, 120, true, 'pending'], [13, 1, -120, -60, true, 'ended'], [14, 1, -60, 60, false, 'active'], [15, 1, -60, 60, true, 'cancelled'], [21, 2, -60, 60, true, 'active'], [31, 3, -60, 60, true, 'active']] as [$id, $activity, $start, $end, $enabled, $status]) {
            $this->connection->table('seckill_sessions')->insert([
                'id' => $id, 'activity_id' => $activity, 'start_time' => Carbon::now()->addMinutes($start)->toDateTimeString(),
                'end_time' => Carbon::now()->addMinutes($end)->toDateTimeString(), 'status' => $status, 'is_enabled' => $enabled,
                'total_quantity' => 20, 'sold_quantity' => 2,
            ]);
            $this->connection->table('products')->insert(['id' => $id, 'product_code' => 'P' . $id, 'category_id' => 1, 'name' => '商品' . $id, 'status' => 'active']);
            $this->connection->table('seckill_session_products')->insert([
                'activity_id' => $activity, 'session_id' => $id, 'product_id' => $id, 'product_sku_id' => $id,
                'original_price' => 1999, 'seckill_price' => 999, 'quantity' => 20, 'sold_quantity' => 2,
            ]);
        }
        $settings = $this->createMock(DomainSystemSettingService::class);
        $settings->method('get')->willReturn('');
        $domain = new DomainApiSeckillQueryService(new SeckillActivityRepository(new SeckillActivity()), new SeckillSessionRepository(new SeckillSession()), new SeckillProductRepository(new SeckillProduct()), $settings);
        $application = new AppApiSeckillProductQueryService($this->createMock(DomainApiSeckillProductDetailService::class), $domain);
        $this->controller = new SeckillProductController($application, new SeckillProductTransformer(new ProductTransformer()));
    }

    public function testExplicitFutureSessionWinsOverLatestActivityAndCarriesActualStock(): void
    {
        $data = $this->query(['activity_id' => '1', 'session_id' => '12']);
        self::assertSame(1, $data['activityId']);
        self::assertSame(12, $data['sessionId']);
        self::assertSame('notStart', $data['statusTag']);
        self::assertSame('12', $data['list'][0]['spuId']);
        self::assertSame(12, $data['list'][0]['sessionId']);
        self::assertSame(1, $data['list'][0]['activityId']);
        self::assertSame(999, $data['list'][0]['price']);
        self::assertSame(18, $data['list'][0]['stockQuantity']);
        self::assertSame(20, $data['list'][0]['totalQuantity']);
        self::assertGreaterThan(7_000_000, $data['time']);
    }

    public function testSessionOnlyResolvesItsRealActivityAndEndedPreview(): void
    {
        $data = $this->query(['session_id' => '13']);
        self::assertSame(1, $data['activityId']);
        self::assertSame(13, $data['sessionId']);
        self::assertSame('expired', $data['statusTag']);
        self::assertSame(0, $data['time']);
        self::assertSame('13', $data['list'][0]['spuId']);
    }

    public function testActivitySessionMismatchIsRejectedRatherThanReturningWrongProducts(): void
    {
        try {
            $this->query(['activity_id' => '2', 'session_id' => '11']);
            self::fail('Cross-activity session must fail.');
        } catch (BusinessException $exception) {
            self::assertSame(422, $exception->getResponse()->code->value);
            self::assertStringContainsString('不属于', $exception->getResponse()->message);
        }
    }

    public function testMissingDisabledAndCancelledSelectionNeverFallsBackToOtherActivity(): void
    {
        foreach ([['activity_id' => 999], ['session_id' => 999], ['activity_id' => 3], ['session_id' => 14], ['session_id' => 15], ['session_id' => 31]] as $params) {
            $data = $this->query($params);
            self::assertSame([], $data['list']);
            self::assertNull($data['activityId']);
            self::assertNull($data['sessionId']);
        }
    }

    public function testDefaultAndActivityOnlyKeepTheirNearestSessionBehavior(): void
    {
        self::assertSame(21, $this->query([])['sessionId']);
        self::assertSame(11, $this->query(['activity_id' => 1])['sessionId']);
        self::assertSame(11, $this->query(['promotion_id' => 1])['sessionId']);
    }

    public function testLimitAndDisabledProductApplyInsideSelectedSession(): void
    {
        $this->connection->table('seckill_session_products')->insert([
            'activity_id' => 1, 'session_id' => 11, 'product_id' => 12, 'product_sku_id' => 112,
            'original_price' => 1999, 'seckill_price' => 899, 'quantity' => 10, 'is_enabled' => false,
        ]);
        self::assertCount(1, $this->query(['activity_id' => 1, 'session_id' => 11])['list']);
        $this->connection->table('seckill_session_products')->where('product_sku_id', 112)->update(['is_enabled' => true]);
        self::assertCount(2, $this->query(['activity_id' => 1, 'session_id' => 11])['list']);
        self::assertCount(1, $this->query(['activity_id' => 1, 'session_id' => 11, 'limit' => 1])['list']);
    }

    private function query(array $params): array
    {
        // 只替换 HTTP 参数提取，真实 toDto → Controller → Application → Domain → MySQL → Transformer。
        $request = $this->getMockBuilder(SeckillPromotionRequest::class)->disableOriginalConstructor()->onlyMethods(['validated'])->getMock();
        $request->method('validated')->willReturn($params);
        return $this->controller->index($request)->data;
    }
}
