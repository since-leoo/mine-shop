<?php

declare(strict_types=1);

namespace HyperfTests\Integration\GroupBuy;

use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyLifecycleService;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyOrderService;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyService;
use App\Infrastructure\Model\GroupBuy\GroupBuy;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use HyperfTests\Support\DatabaseTestCase;

final class GroupLifecycleTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once BASE_PATH . '/migrations/2024_01_01_000018_create_mall_group_buys_table.php';
        require_once BASE_PATH . '/migrations/2024_01_01_000019_create_mall_group_buy_orders_table.php';
        (new \CreateMallGroupBuysTable())->up();
        (new \CreateMallGroupBuyOrdersTable())->up();
        $this->connection->table('group_buys')->insert([
            'id' => 1, 'title' => 'test', 'product_id' => 1, 'sku_id' => 1, 'original_price' => 800, 'group_price' => 600,
            'start_time' => date('Y-m-d H:i:s', time() - 3600), 'end_time' => date('Y-m-d H:i:s', time() + 86400),
            'status' => 'active', 'total_quantity' => 20, 'max_people' => 4, 'sold_quantity' => 3,
        ]);
        foreach ([1, 2, 3] as $id) {
            $this->connection->table('group_buy_orders')->insert([
                'group_buy_id' => 1, 'order_id' => $id, 'member_id' => $id, 'group_no' => 'GROUP', 'is_leader' => $id === 1,
                'original_price' => 800, 'group_price' => 600, 'total_amount' => 600, 'join_time' => date('Y-m-d H:i:s'),
                'expire_time' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        }
    }

    public function testUnpaidParticipantsStayPendingAndLaterPaymentDoesNotCountGroupTwice(): void
    {
        $repo = new GroupBuyOrderRepository(new GroupBuyOrder());
        $service = new DomainGroupBuyLifecycleService(new DomainGroupBuyService(new GroupBuyRepository(new GroupBuy())), $repo);
        foreach ([1, 2, 2] as $id) {
            $this->connection->transaction(fn () => $service->onOrderPaid($id));
        }
        self::assertSame('pending', $repo->findByOrderId(3)->status);
        self::assertSame('grouped', $repo->findByOrderId(1)->status);
        $this->connection->transaction(fn () => $service->onOrderPaid(3));
        self::assertNotNull($repo->findByOrderId(3)->pay_time);
        self::assertSame('grouped', $repo->findByOrderId(3)->status);
        self::assertSame(1, (int) $this->connection->table('group_buys')->value('success_group_count'));
    }

    public function testCancellationReleasesAllocationExactlyOnce(): void
    {
        $repo = new GroupBuyOrderRepository(new GroupBuyOrder());
        $service = new DomainGroupBuyLifecycleService(new DomainGroupBuyService(new GroupBuyRepository(new GroupBuy())), $repo);
        $this->connection->transaction(fn () => $service->onOrderCancelled(3));
        $this->connection->transaction(fn () => $service->onOrderCancelled(3));
        self::assertSame('cancelled', $repo->findByOrderId(3)->status);
        self::assertSame(2, (int) $this->connection->table('group_buys')->value('sold_quantity'));
    }

    public function testOtherActivityCannotJoinExistingGroup(): void
    {
        $service = new DomainGroupBuyOrderService(new DomainGroupBuyService(new GroupBuyRepository(new GroupBuy())), new GroupBuyOrderRepository(new GroupBuyOrder()));
        $this->expectException(\DomainException::class);
        $service->validateJoinGroup('GROUP', 2, 4);
    }
}
