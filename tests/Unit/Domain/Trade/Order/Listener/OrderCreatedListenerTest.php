<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\Listener;

use App\Domain\Catalog\Product\Repository\ProductSkuRepository;
use App\Domain\Member\Api\Command\DomainApiMemberCartCommandService;
use App\Domain\Member\Api\Query\DomainApiMemberCartQueryService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Event\OrderCreatedEvent;
use App\Domain\Trade\Order\Listener\OrderCreatedListener;
use App\Infrastructure\Abstract\ICache;
use Hyperf\Logger\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 * @coversNothing
 */
final class OrderCreatedListenerTest extends TestCase
{
    public function testProcessRemovesCartItemsForCartOrder(): void
    {
        $deleted = new \ArrayObject();
        $cache = new class($deleted) extends ICache {
            public function __construct(private readonly \ArrayObject $deleted) {}

            public function setPrefix(string $prefix): self
            {
                return $this;
            }

            public function hDel(string $key, string $field): bool
            {
                $this->deleted->append([$key, $field]);
                return true;
            }
        };

        $cartCommandService = new DomainApiMemberCartCommandService(
            $cache,
            $this->newInstanceWithoutConstructor(ProductSkuRepository::class),
            $this->newInstanceWithoutConstructor(DomainApiMemberCartQueryService::class),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $loggerFactory = $this->createMock(LoggerFactory::class);
        $loggerFactory->method('get')->with('order')->willReturn($logger);

        $listener = new OrderCreatedListener($loggerFactory, $cartCommandService);
        $listener->process(new OrderCreatedEvent($this->makeOrder(true)));

        self::assertSame([
            ['1001', '11'],
            ['1001', '22'],
        ], $deleted->getArrayCopy());
    }

    public function testProcessSkipsCartRemovalForNonCartOrder(): void
    {
        $deleted = new \ArrayObject();
        $cache = new class($deleted) extends ICache {
            public function __construct(private readonly \ArrayObject $deleted) {}

            public function setPrefix(string $prefix): self
            {
                return $this;
            }

            public function hDel(string $key, string $field): bool
            {
                $this->deleted->append([$key, $field]);
                return true;
            }
        };

        $cartCommandService = new DomainApiMemberCartCommandService(
            $cache,
            $this->newInstanceWithoutConstructor(ProductSkuRepository::class),
            $this->newInstanceWithoutConstructor(DomainApiMemberCartQueryService::class),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $loggerFactory = $this->createMock(LoggerFactory::class);
        $loggerFactory->method('get')->with('order')->willReturn($logger);

        $listener = new OrderCreatedListener($loggerFactory, $cartCommandService);
        $listener->process(new OrderCreatedEvent($this->makeOrder(false)));

        self::assertSame([], $deleted->getArrayCopy());
    }

    private function newInstanceWithoutConstructor(string $class): object
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }

    private function makeOrder(bool $fromCart): OrderEntity
    {
        $order = new OrderEntity();
        $order->setOrderNo('ORD202603170001');
        $order->setMemberId(1001);
        $order->setPayAmount(19900);
        $order->setExtra('from_cart', $fromCart);

        $first = new OrderItemEntity();
        $first->setSkuId(11);
        $first->setQuantity(1);
        $order->addItem($first);

        $second = new OrderItemEntity();
        $second->setSkuId(22);
        $second->setQuantity(2);
        $order->addItem($second);

        return $order;
    }
}
