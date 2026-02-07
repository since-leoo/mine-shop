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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Coupon\Service\CouponUserService;
use App\Domain\Member\Service\MemberAddressService;
use App\Domain\Order\Contract\OrderSubmitInput;
use App\Domain\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\Order\Service\OrderService;
use App\Domain\Order\Service\OrderStockService;
use App\Domain\SystemSetting\Service\MallSettingService;
use App\Domain\SystemSetting\ValueObject\OrderSetting;
use App\Domain\SystemSetting\ValueObject\ProductSetting;
use DG\BypassFinals;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Feature: order-checkout-refactor, Property 2: 商品下架时提交不扣库存.
 *
 * For any 包含已下架商品的订单输入，调用 OrderService::submit() 应在 buildDraft 阶段抛出异常，
 * 且 StockService::reserve() 不被调用。
 *
 * **Validates: Requirements 3.1, 3.2**
 *
 * @internal
 * @coversNothing
 */
final class SubmitOrderPropertyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    /**
     * Property 2: 商品下架时提交不扣库存.
     *
     * For any order input containing offline products, calling OrderService::submit()
     * should throw an exception during buildDraft, and StockService::reserve() must NOT be called.
     *
     * Validates: Requirements 3.1, 3.2
     */
    public function testBuildDraftExceptionPreventsStockReserve(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $memberId = random_int(1, 99999);
            $skuId = random_int(1, 99999);
            $quantity = random_int(1, 10);
            $totalAmount = random_int(100, 999999);
            $exceptionMessage = '商品 测试商品_' . $i . ' 已下架';

            $input = $this->createMockInput($memberId, $skuId, $quantity, $totalAmount);

            // Strategy that throws on buildDraft (simulating product offline)
            $strategy = $this->createThrowingStrategy($exceptionMessage);
            $strategyFactory = new OrderTypeStrategyFactory([$strategy]);

            // Mock StockService — reserve must NEVER be called
            $stockService = \Mockery::mock(OrderStockService::class);
            $stockService->shouldNotReceive('reserve');
            $stockService->shouldNotReceive('acquireLocks');

            // Mock MallSettingService
            $mallSettingService = \Mockery::mock(MallSettingService::class);
            $productSetting = new ProductSetting(false, 9, 20, true, []);
            $orderSetting = new OrderSetting(30, 7, 15, true, 'system', '400-888-1000');
            $mallSettingService->shouldReceive('product')->andReturn($productSetting);
            $mallSettingService->shouldReceive('order')->andReturn($orderSetting);

            // Mock repository (should not be called)
            $repository = \Mockery::mock(OrderRepository::class);
            $repository->shouldNotReceive('save');

            // Mock address service
            $addressService = \Mockery::mock(MemberAddressService::class);
            $addressService->shouldReceive('default')->andReturn([
                'name' => '张三',
                'phone' => '13800138000',
                'province' => '广东',
                'city' => '广州',
                'district' => '天河',
                'detail' => '体育西路',
            ]);

            $orderService = new OrderService(
                $repository,
                $strategyFactory,
                $stockService,
                $mallSettingService,
                $addressService,
                \Mockery::mock(CouponUserService::class),
            );

            // Act & Assert: submit should throw, reserve should NOT be called
            $threwException = false;
            try {
                $orderService->submit($input);
            } catch (\RuntimeException $e) {
                $threwException = true;
                self::assertSame(
                    $exceptionMessage,
                    $e->getMessage(),
                    \sprintf('Iteration %d: Exception message should match buildDraft error', $i),
                );
            }

            self::assertTrue(
                $threwException,
                \sprintf('Iteration %d: submit() should throw RuntimeException when buildDraft fails (product offline)', $i),
            );

            // Mockery automatically verifies shouldNotReceive constraints via MockeryPHPUnitIntegration
            \Mockery::close();
        }
    }

    /**
     * Unit test: buildDraft is called BEFORE reserve in the submit flow.
     *
     * Uses a tracking strategy and mock stock service to verify execution order.
     *
     * Validates: Requirement 3.1
     */
    public function testBuildDraftIsCalledBeforeReserve(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $callOrder = [];

            $memberId = random_int(1, 99999);
            $skuId = random_int(1, 99999);
            $quantity = random_int(1, 10);
            $unitPrice = random_int(1, 1000);
            $payAmountYuan = (float) bcmul((string) $unitPrice, (string) $quantity, 2);
            $totalAmountCent = (int) round($payAmountYuan * 100);

            $input = $this->createMockInput($memberId, $skuId, $quantity, $totalAmountCent);

            // Strategy that tracks call order and sets price
            $strategy = $this->createTrackingStrategy($callOrder, $unitPrice);
            $strategyFactory = new OrderTypeStrategyFactory([$strategy]);

            // Mock StockService that tracks call order
            $stockService = \Mockery::mock(OrderStockService::class);
            $stockService->shouldReceive('acquireLocks')
                ->once()
                ->andReturnUsing(static function () use (&$callOrder) {
                    $callOrder[] = 'acquireLocks';
                    return ['lock1' => 'token1'];
                });
            $stockService->shouldReceive('reserve')
                ->once()
                ->andReturnUsing(static function () use (&$callOrder) {
                    $callOrder[] = 'reserve';
                });
            $stockService->shouldReceive('releaseLocks')
                ->once()
                ->andReturnUsing(static function () use (&$callOrder) {
                    $callOrder[] = 'releaseLocks';
                });

            $mallSettingService = \Mockery::mock(MallSettingService::class);
            $productSetting = new ProductSetting(false, 9, 20, true, []);
            $orderSetting = new OrderSetting(30, 7, 15, true, 'system', '400-888-1000');
            $mallSettingService->shouldReceive('product')->andReturn($productSetting);
            $mallSettingService->shouldReceive('order')->andReturn($orderSetting);

            $repository = \Mockery::mock(OrderRepository::class);
            $repository->shouldReceive('save')
                ->once()
                ->andReturnUsing(static function (OrderEntity $entity) use (&$callOrder) {
                    $callOrder[] = 'save';
                    $entity->setOrderNo('TEST' . random_int(100000, 999999));
                    return $entity;
                });

            $addressService = \Mockery::mock(MemberAddressService::class);
            $addressService->shouldReceive('default')->andReturn([
                'name' => '张三',
                'phone' => '13800138000',
                'province' => '广东',
                'city' => '广州',
                'district' => '天河',
                'detail' => '体育西路',
            ]);

            $orderService = new OrderService(
                $repository,
                $strategyFactory,
                $stockService,
                $mallSettingService,
                $addressService,
                \Mockery::mock(CouponUserService::class),
            );

            $orderService->submit($input);

            // Verify buildDraft was called before reserve
            $buildDraftIndex = array_search('buildDraft', $callOrder, true);
            $reserveIndex = array_search('reserve', $callOrder, true);

            self::assertNotFalse(
                $buildDraftIndex,
                \sprintf('Iteration %d: buildDraft should have been called', $i),
            );
            self::assertNotFalse(
                $reserveIndex,
                \sprintf('Iteration %d: reserve should have been called', $i),
            );
            self::assertLessThan(
                $reserveIndex,
                $buildDraftIndex,
                \sprintf(
                    'Iteration %d: buildDraft (index %d) must be called before reserve (index %d). Order: [%s]',
                    $i,
                    $buildDraftIndex,
                    $reserveIndex,
                    implode(', ', $callOrder),
                ),
            );

            \Mockery::close();
        }
    }

    /**
     * Create a mock OrderSubmitInput with random data.
     */
    private function createMockInput(int $memberId, int $skuId, int $quantity, int $totalAmount): OrderSubmitInput
    {
        return new class($memberId, $skuId, $quantity, $totalAmount) implements OrderSubmitInput {
            public function __construct(
                private readonly int $memberId,
                private readonly int $skuId,
                private readonly int $quantity,
                private readonly int $totalAmount,
            ) {}

            public function getMemberId(): int
            {
                return $this->memberId;
            }

            public function getOrderType(): string
            {
                return 'normal';
            }

            public function getGoodsRequestList(): array
            {
                return [['sku_id' => $this->skuId, 'quantity' => $this->quantity]];
            }

            public function getAddressId(): ?int
            {
                return null;
            }

            public function getUserAddress(): ?array
            {
                return null;
            }

            public function getCouponList(): ?array
            {
                return null;
            }

            public function getBuyerRemark(): string
            {
                return '测试备注';
            }

            public function getTotalAmount(): int
            {
                return $this->totalAmount;
            }

            public function getUserName(): ?string
            {
                return null;
            }
        };
    }

    /**
     * Create a strategy that throws RuntimeException on buildDraft (simulating product offline).
     */
    private function createThrowingStrategy(string $message): OrderTypeStrategyInterface
    {
        return new class($message) implements OrderTypeStrategyInterface {
            public function __construct(private readonly string $message) {}

            public function type(): string
            {
                return 'normal';
            }

            public function validate(OrderEntity $orderEntity): void {}

            public function buildDraft(OrderEntity $orderEntity): OrderEntity
            {
                throw new \RuntimeException($this->message);
            }

            public function applyCoupon(OrderEntity $orderEntity, array $couponList): void {}

            public function adjustPrice(OrderEntity $orderEntity): void {}

            public function postCreate(OrderEntity $orderEntity): void {}
        };
    }

    /**
     * Create a strategy that tracks call order and sets a known price on the entity.
     *
     * @param array<int, string> $callOrder Reference to call order tracking array
     * @param int $unitPrice Unit price to set on items during buildDraft
     */
    private function createTrackingStrategy(array &$callOrder, int $unitPrice): OrderTypeStrategyInterface
    {
        return new class($callOrder, $unitPrice) implements OrderTypeStrategyInterface {
            /**
             * @param array<int, string> $callOrder
             */
            public function __construct(
                private array &$callOrder,
                private readonly int $unitPrice,
            ) {}

            public function type(): string
            {
                return 'normal';
            }

            public function validate(OrderEntity $orderEntity): void
            {
                $this->callOrder[] = 'validate';
            }

            public function buildDraft(OrderEntity $orderEntity): OrderEntity
            {
                $this->callOrder[] = 'buildDraft';
                // Simulate buildDraft: set unit prices on items and sync price detail
                foreach ($orderEntity->getItems() as $item) {
                    $item->setUnitPrice($this->unitPrice);
                }
                $orderEntity->syncPriceDetailFromItems();
                return $orderEntity;
            }

            public function applyCoupon(OrderEntity $orderEntity, array $couponList): void
            {
                $this->callOrder[] = 'applyCoupon';
            }

            public function adjustPrice(OrderEntity $orderEntity): void
            {
                $this->callOrder[] = 'adjustPrice';
            }

            public function postCreate(OrderEntity $orderEntity): void
            {
                $this->callOrder[] = 'postCreate';
            }
        };
    }
}
