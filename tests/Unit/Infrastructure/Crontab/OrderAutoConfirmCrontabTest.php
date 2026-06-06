<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Infrastructure\Crontab;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Crontab\OrderAutoConfirmCrontab;
use App\Infrastructure\Model\Order\Order;
use DG\BypassFinals;
use Hyperf\Database\Model\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 * @coversNothing
 */
final class OrderAutoConfirmCrontabTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testExecuteCompletesAutoConfirmableOrders(): void
    {
        $order = $this->makeOrder();
        $repository = $this->createMock(OrderRepository::class);
        $settings = $this->makeSettings(autoConfirmDays: 7);

        $repository
            ->expects(self::once())
            ->method('findAutoConfirmableOrders')
            ->with(self::isInstanceOf(\Carbon\Carbon::class), 200)
            ->willReturn(new Collection([$order]));

        $repository
            ->expects(self::once())
            ->method('complete')
            ->with(self::callback(static fn ($entity): bool => $entity->getId() === 1001));

        $crontab = new OrderAutoConfirmCrontab($repository, $settings, $this->createMock(LoggerInterface::class));
        $crontab->execute();
    }

    public function testExecuteSkipsWhenAutoConfirmDaysDisabled(): void
    {
        $repository = $this->createMock(OrderRepository::class);
        $repository->expects(self::never())->method('findAutoConfirmableOrders');

        $crontab = new OrderAutoConfirmCrontab(
            $repository,
            $this->makeSettings(autoConfirmDays: 0),
            $this->createMock(LoggerInterface::class),
        );

        $crontab->execute();
    }

    private function makeSettings(int $autoConfirmDays): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings
            ->method('order')
            ->willReturn(new OrderSetting(30, $autoConfirmDays, 15, true, 'system', '400-888-1000'));

        return $settings;
    }

    private function makeOrder(): Order
    {
        $order = new class extends Order {
            public function __construct() {}
        };
        $order->setRawAttributes([
            'id' => 1001,
            'order_no' => 'ORD202606060001',
            'member_id' => 3003,
            'order_type' => 'normal',
            'status' => 'shipped',
            'goods_amount' => 10000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total_amount' => 10000,
            'pay_amount' => 10000,
            'pay_status' => 'paid',
            'shipping_status' => 'shipped',
            'package_count' => 1,
        ], true);

        return $order;
    }
}
