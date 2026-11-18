<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyOrderService;
use App\Domain\Trade\GroupBuy\Strategy\GroupBuyOrderStrategy;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use App\Domain\Trade\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Trade\Seckill\Service\SeckillCacheService;
use App\Domain\Trade\Seckill\Strategy\SeckillOrderStrategy;
use App\Infrastructure\Exception\System\Handler\AppExceptionHandler;
use App\Interface\Api\DTO\Order\OrderPreviewDto;
use Hyperf\Config\Config;
use Hyperf\ExceptionHandler\Propagation;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\Logger\LoggerFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

final class BusinessValidationResponseTest extends TestCase
{
    public function testUnavailableSkuFromRealOrderInputKeepsActionableMessage(): void
    {
        $snapshots = $this->createMock(ProductSnapshotInterface::class);
        $snapshots->method('getSkuSnapshots')->with([88])->willReturn([]);
        $strategy = $this->normal($snapshots);
        $this->assertPublicFailure(fn () => $strategy->validate($this->order()), 'SKU 88 不存在或已下架');
    }

    public function testCouponThresholdFailureFromRealOrderKeepsMessage(): void
    {
        $coupons = $this->createMock(CouponServiceInterface::class);
        $coupons->method('findUsableCoupon')->with(7, 10)->willReturn(['id' => 10, 'coupon_id' => 1, 'type' => 'fixed', 'value' => 100, 'min_amount' => 500, 'name' => '满减券', 'status' => 'active']);
        $strategy = $this->normal($this->createMock(ProductSnapshotInterface::class), $coupons);
        $this->assertPublicFailure(fn () => $strategy->applyCoupon($this->order(), 10), '优惠券 满减券 需满 500 分可用，当前商品金额 0 分');
    }

    public function testGroupBuyCouponRejectionKeepsMessage(): void
    {
        $strategy = new GroupBuyOrderStrategy($this->createMock(ProductSnapshotInterface::class), $this->createMock(DomainGroupBuyOrderService::class));
        $this->assertPublicFailure(fn () => $strategy->applyCoupon($this->order('group_buy'), 10), '拼团订单不支持使用优惠券');
    }

    #[DataProvider('seckillRejections')]
    public function testSeckillStockAndPurchaseLimitMessagesSurviveProductionHandler(int $available, int $quantity, int $purchased, string $expected): void
    {
        $cache = $this->createMock(SeckillCacheService::class);
        $cache->method('getSession')->with(1)->willReturn(SeckillSessionEntity::reconstitute(
            1, 1, date('Y-m-d H:i:s', time() - 60), date('Y-m-d H:i:s', time() + 3600), 'active', 1, 10, 0, 0, true, [], null,
        ));
        $cache->method('getProductBySkuId')->with(1, 88)->willReturn(SeckillProductEntity::reconstitute(1, 1, 1, 1, 88, 200, 100, $available, 0, 1, 0, true));
        $orders = $this->createMock(SeckillOrderRepository::class);
        $orders->method('getMemberPurchasedQuantity')->willReturn($purchased);
        $strategy = new SeckillOrderStrategy(
            $this->createMock(ProductSnapshotInterface::class), $cache,
            $this->createMock(SeckillSessionRepository::class), $this->createMock(SeckillProductRepository::class), $orders,
        );
        $this->assertPublicFailure(fn () => $strategy->validate($this->order('seckill', $quantity)), $expected);
    }

    public static function seckillRejections(): array
    {
        return [
            'stock exhausted' => [0, 1, 0, '秒杀商品库存不足或已下架'],
            'purchase limit' => [10, 1, 1, '超出限购数量，每人限购 1 件'],
        ];
    }

    #[DataProvider('orderStateRejections')]
    public function testOrderStateAndPackageValidationRemainVisible(string $status, string $operation, string $expected): void
    {
        $order = $this->order();
        $order->setStatus($status);
        $order->setShipEntity(new OrderShipEntity());
        $this->assertPublicFailure(fn () => $order->{$operation}(), $expected);
        self::assertSame($status, $order->getStatus());
    }

    public static function orderStateRejections(): array
    {
        return [
            ['pending', 'ship', '当前订单状态不可发货'],
            ['paid', 'ship', '请至少提供一个包裹信息'],
            ['pending', 'complete', '当前订单状态不可完成'],
            ['cancelled', 'markPaid', '当前订单状态不可支付'],
        ];
    }

    public function testUsedCouponCannotBeUsedOrExpiredAgain(): void
    {
        $coupon = CouponUserEntity::issue(1, 7, '2026-01-01 00:00:00', '2026-12-31 23:59:59');
        $coupon->markUsed(orderId: 9);
        $this->assertPublicFailure(fn () => $coupon->markUsed(orderId: 10), '仅未使用的优惠券可以置为已使用');
        $this->assertPublicFailure(fn () => $coupon->markExpired(), '仅未使用的优惠券可以置为过期');
        self::assertSame('used', $coupon->getStatus());
        self::assertSame(9, $coupon->getOrderId());
    }

    public function testUnsupportedOrderTypeRemainsVisible(): void
    {
        $this->assertPublicFailure(fn () => (new OrderTypeStrategyFactory())->make('unsupported'), '不支持的订单类型：unsupported');
    }

    public function testSnapshotInfrastructureFailureIsStillHiddenAndNotConvertedToBusinessFailure(): void
    {
        $failure = new \RuntimeException('Redis connection secret=fixture');
        $snapshots = $this->createMock(ProductSnapshotInterface::class);
        $snapshots->method('getSkuSnapshots')->willThrowException($failure);
        try {
            $this->normal($snapshots)->validate($this->order());
            self::fail('Snapshot failure must propagate');
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);
            self::assertSame('系统繁忙，请稍后重试', $this->responseMessage($error));
        }
    }

    private function normal(ProductSnapshotInterface $snapshots, ?CouponServiceInterface $coupons = null): NormalOrderStrategy
    {
        return new NormalOrderStrategy($snapshots, $coupons ?? $this->createMock(CouponServiceInterface::class), $this->createMock(FreightServiceInterface::class));
    }

    private function order(string $type = 'normal', int $quantity = 1): OrderEntity
    {
        $input = new OrderPreviewDto();
        $input->member_id = 7;
        $input->order_type = $type;
        $input->goods_request_list = [['sku_id' => 88, 'quantity' => $quantity]];
        $input->session_id = 1;
        $order = new OrderEntity();
        $order->initFromInput($input);
        return $order;
    }

    private function assertPublicFailure(callable $operation, string $expected): void
    {
        $failure = null;
        try {
            $operation();
        } catch (\Throwable $error) {
            $failure = $error;
        }
        self::assertNotNull($failure);
        self::assertSame(\DomainException::class, $failure::class);
        self::assertSame($expected, $this->responseMessage($failure));
    }

    private function responseMessage(\Throwable $error): string
    {
        $loggers = $this->createMock(LoggerFactory::class);
        $loggers->method('get')->willReturn(new NullLogger());
        $handler = new AppExceptionHandler(new Config(['debug' => false]), $this->createMock(ContainerInterface::class), $loggers);
        try {
            $response = $handler->handle($error, new Response());
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::assertArrayNotHasKey('throwable', $body);
            return $body['message'];
        } finally {
            Propagation::instance()->setPropagationStopped(false);
        }
    }
}
