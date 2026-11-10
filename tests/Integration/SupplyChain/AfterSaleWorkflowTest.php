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

namespace HyperfTests\Integration\SupplyChain;

use App\Domain\Infrastructure\Plugin\Contract\CommerceAfterSaleInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use HyperfTests\Support\DatabaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Plugin\SupplyChain\Application\Commad\AppSupplyAfterSaleService;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\Service\DomainSupplyAfterSaleService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyTaskService;
use Plugin\SupplyChain\Interfaces\AfterSaleInterface;
use Plugin\SupplyChain\Interfaces\Data\AfterSaleCommand;
use Plugin\SupplyChain\Interfaces\SupplyChainClientInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

/**
 * @internal @coversNothing
 * @coversNothing
 */
final class AfterSaleWorkflowTest extends DatabaseTestCase
{
    private SupplyRepository $repository;

    private DomainSupplyTaskService $tasks;

    private AppSupplyAfterSaleService $service;

    private AfterSaleInterface&MockObject $provider;

    private array $customers;

    private int $purchaseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrate('plugins/supply-chain/Database/Migrations/2026_10_16_000100_create_supply_chain_tables.php');
        $this->repository = new SupplyRepository();
        $this->tasks = new DomainSupplyTaskService($this->repository);
        $this->customers = [10 => ['id' => 10, 'order_id' => 20, 'order_item_id' => 30, 'quantity' => 2,
            'apply_amount' => 300, 'refund_amount' => 0, 'status' => 'pending_review', 'images' => [],
            'order_item' => ['fulfillment_provider' => 'supply_chain']]];
        $customerAfterSales = $this->createMock(CommerceAfterSaleInterface::class);
        $customerAfterSales->method('find')->willReturnCallback(fn ($id) => $this->customers[$id] ?? null);
        foreach (['approve', 'reject', 'receive', 'refund'] as $method) {
            $customerAfterSales->expects(self::never())->method($method);
        }
        $customerOrders = $this->createMock(CommerceOrderInterface::class);
        $customerOrders->method('find')->willReturn(['id' => 20, 'status' => 'paid', 'pay_status' => 'paid']);
        $domain = new DomainSupplyAfterSaleService($this->repository, $this->tasks, $customerAfterSales, $customerOrders);
        $transaction = $this->createMock(TransactionManagerInterface::class);
        $transaction->method('run')->willReturnCallback(fn (callable $callback) => $this->connection->transaction($callback));
        $this->provider = $this->createMock(AfterSaleInterface::class);
        $this->provider->method('types')->willReturnCallback(function (string $id): array {
            self::assertSame(0, $this->connection->transactionLevel(), 'Policy HTTP must be outside transactions');
            self::assertSame('500', $id);
            return [0 => '仅退款', 1 => '退货退款'];
        });
        $client = $this->createMock(SupplyChainClientInterface::class);
        $client->method('afterSales')->willReturn($this->provider);
        $manager = $this->createMock(SupplyChainManagerInterface::class);
        $manager->method('forConnection')->with(1)->willReturn($client);
        $this->service = new AppSupplyAfterSaleService($domain, $manager, $transaction);
        $this->repository->insert('connections', ['id' => 1, 'name' => '测试供货方', 'api_domain' => 'https://supplier.test',
            'app_key' => 'fixture-key', 'app_secret_ciphertext' => 'fixture-only', 'settings' => []]);
        $this->purchaseId = $this->repository->insert('orders', ['connection_id' => 1, 'order_id' => 20,
            'order_no' => 'ORDER20', 'merchant_order_no' => 'SC20-1-FIXED', 'group_key' => 'fixture', 'status' => 'accepted',
            'snapshot' => [], 'external_orders' => [['external_order_no' => 'UP1', 'freight_cents' => 10,
                'items' => [['external_order_item_id' => '500', 'external_sku_id' => '600', 'quantity' => 2,
                    'amount_cents' => 200, 'technical_service_fee_cents' => 10]]]]]);
        $this->repository->insert('order_items', ['supply_order_id' => $this->purchaseId, 'order_item_id' => 30,
            'sku_id' => 40, 'external_product_id' => '700', 'external_sku_id' => '600', 'quantity' => 2, 'cost_price' => 100, 'binding_version' => 1]);
    }

    public function testEventOnlyPersistsReviewAndExplicitSubmissionExecutesOutsideTransaction(): void
    {
        $this->service->changed(10, 'apply');
        $this->service->changed(10, 'apply');
        self::assertSame(1, $this->repository->query('after_sales')->count());
        self::assertSame(0, $this->repository->query('tasks')->count());
        $id = $this->localId(10);
        self::assertSame('pending_review', $this->repository->require('after_sales', $id)['status']);
        $this->provider->expects(self::once())->method('create')->willReturnCallback(function (AfterSaleCommand $command): string {
            self::assertSame(0, $this->connection->transactionLevel());
            self::assertSame('SC20-1-FIXED', $command->merchantOrderNo);
            self::assertSame('500', $command->externalOrderItemId);
            self::assertSame(100, $command->amountCents);
            return 'AFTER1';
        });
        $task = $this->service->submit($id, $this->input(), 7);
        $result = $this->service->execute($this->claim($task['task_id']));
        self::assertSame('submitted', $result['status']);
        self::assertSame('AFTER1', $this->repository->require('after_sales', $id)['external_after_sale_id']);
    }

    public function testOverBudgetFailsBeforeProviderSubmissionAndOtherCasesReserveQuantity(): void
    {
        $this->provider->expects(self::never())->method('create');
        $this->service->changed(10, 'apply');
        try {
            $this->service->submit($this->localId(10), array_replace($this->input(), ['amount_cents' => 101]), 1);
            self::fail('A half-line refund cannot spend the entire line budget');
        } catch (\DomainException $error) {
            self::assertStringContainsString('金额', $error->getMessage());
        }
        $this->service->submit($this->localId(10), array_replace($this->input(), ['quantity' => 2, 'amount_cents' => 200]), 1);
        $this->customers[11] = array_replace($this->customers[10], ['id' => 11]);
        $this->service->changed(11, 'apply');
        $this->expectException(\DomainException::class);
        $this->service->submit($this->localId(11), $this->input(), 1);
    }

    public function testAmbiguousSubmissionOnlyQueriesAndDoesNotClaimAnOlderRemoteAfterSale(): void
    {
        $this->service->changed(10, 'apply');
        $id = $this->localId(10);
        $this->provider->expects(self::once())->method('create')->willThrowException(new \RuntimeException('timeout', 10001));
        $task = $this->claim($this->service->submit($id, $this->input(), 7)['task_id']);
        try {
            $this->service->execute($task);
            self::fail('Expected timeout');
        } catch (\RuntimeException $error) {
            self::assertSame(10001, $error->getCode());
            $this->connection->transaction(fn () => $this->tasks->fail($task, $error));
        }
        self::assertSame('unknown', $this->repository->require('tasks', $task['id'])['status']);
        self::assertSame('unknown', $this->repository->require('after_sales', $id)['status']);
        $this->provider->expects(self::once())->method('findByOrderItem')->with('500')->willReturn(['external_after_sale_id' => 'OLDER1', 'provider_status' => 1]);
        $queryId = (int) $this->repository->query('tasks')->where('type', 'after_sale_query')->value('id');
        $this->service->execute($this->claim($queryId));
        $row = $this->repository->require('after_sales', $id);
        self::assertNull($row['external_after_sale_id']);
        self::assertSame('OLDER1', $row['snapshot']['candidate_external_after_sale_id']);
        $this->expectException(\DomainException::class);
        $this->service->submit($id, $this->input(), 7);
    }

    public function testSplitSameSkuProcurementRequiresManualAllocation(): void
    {
        $this->service->changed(10, 'apply');
        $purchase = $this->repository->require('orders', $this->purchaseId);
        $orders = $purchase['external_orders'];
        $orders[0]['items'][] = array_replace($orders[0]['items'][0], ['external_order_item_id' => '501']);
        $this->repository->update('orders', $this->purchaseId, ['external_orders' => $orders]);
        $this->provider->expects(self::never())->method('create');
        $this->expectExceptionMessage('需分拆');
        $this->service->submit($this->localId(10), $this->input(), 1);
    }

    public function testCustomerCancellationBeforeDispatchPreventsRemoteWrite(): void
    {
        $this->service->changed(10, 'apply');
        $taskId = $this->service->submit($this->localId(10), $this->input(), 1)['task_id'];
        $this->customers[10]['status'] = 'closed';
        $this->service->changed(10, 'cancel');
        $this->provider->expects(self::never())->method('create');
        $this->expectException(\DomainException::class);
        $this->service->execute($this->claim($taskId));
    }

    public function testExpiredLeaseCannotPublishLateProviderSuccess(): void
    {
        $this->service->changed(10, 'apply');
        $id = $this->localId(10);
        $task = $this->claim($this->service->submit($id, $this->input(), 1)['task_id']);
        $this->provider->expects(self::once())->method('create')->willReturnCallback(function () use ($task): string {
            $this->repository->update('tasks', $task['id'], ['lease_expires_at' => date('Y-m-d H:i:s', time() - 1)]);
            return 'AFTER1';
        });
        try {
            $this->service->execute($task);
            self::fail('Expired worker may not publish success');
        } catch (\RuntimeException $error) {
            self::assertSame(10001, $error->getCode());
        }
        self::assertNull($this->repository->require('after_sales', $id)['external_after_sale_id']);
    }

    public function testReturnShipmentUsesExplicitCarrierMappingAndNoCustomerRefund(): void
    {
        $this->service->changed(10, 'apply');
        $id = $this->localId(10);
        $this->provider->expects(self::once())->method('create')->willReturn('AFTER1');
        $this->service->execute($this->claim($this->service->submit($id, $this->input(), 1)['task_id']));
        $this->repository->update('connections', 1, ['settings' => ['return_carriers' => ['顺丰速运' => ['name' => '顺丰速运', 'code' => 'SF']]]]);
        $this->customers[10]['buyer_return_logistics_company'] = '顺丰速运';
        $this->customers[10]['buyer_return_logistics_no'] = 'SF123';
        $this->service->changed(10, 'return_shipment');
        $taskId = (int) $this->repository->query('tasks')->where('type', 'after_sale_return')->value('id');
        $this->provider->expects(self::once())->method('shipReturn')->with('AFTER1', '顺丰速运', 'SF', 'SF123')->willReturnCallback(function (): void {
            self::assertSame(0, $this->connection->transactionLevel());
        });
        $this->service->execute($this->claim($taskId));
        self::assertSame('return_submitted', $this->repository->require('after_sales', $id)['status']);
    }

    public function testCancelledDuringCreateQueuesSupplierCancellationAfterSuccess(): void
    {
        $this->service->changed(10, 'apply');
        $id = $this->localId(10);
        $this->provider->expects(self::once())->method('create')->willReturnCallback(function (): string {
            $this->customers[10]['status'] = 'closed';
            $this->service->changed(10, 'cancel');
            return 'AFTER1';
        });
        $this->service->execute($this->claim($this->service->submit($id, $this->input(), 1)['task_id']));
        self::assertSame('cancel_queued', $this->repository->require('after_sales', $id)['status']);
        $taskId = (int) $this->repository->query('tasks')->where('type', 'after_sale_cancel')->value('id');
        $this->provider->expects(self::once())->method('close')->with('AFTER1')->willReturnCallback(function (): void {
            self::assertSame(0, $this->connection->transactionLevel());
        });
        $this->service->execute($this->claim($taskId));
        self::assertSame('closed', $this->repository->require('after_sales', $id)['status']);
    }

    public function testDefinitelyUnsentRateLimitRetainsQueuedStateForBoundedRetry(): void
    {
        $this->service->changed(10, 'apply');
        $id = $this->localId(10);
        $this->provider->expects(self::once())->method('create')->willThrowException(new \RuntimeException('local throttle', 429));
        $task = $this->claim($this->service->submit($id, $this->input(), 1)['task_id']);
        try {
            $this->service->execute($task);
            self::fail('Expected local rate limit');
        } catch (\RuntimeException $error) {
            $this->connection->transaction(fn () => $this->tasks->fail($task, $error));
        }
        self::assertSame('queued', $this->repository->require('after_sales', $id)['status']);
        self::assertSame('retry_wait', $this->repository->require('tasks', $task['id'])['status']);
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'after_sale_query')->count());
    }

    public function testMissingCarrierCodeIsVisibleAndNeverGuessedFromCompanyName(): void
    {
        $this->service->changed(10, 'apply');
        $id = $this->localId(10);
        $this->provider->method('create')->willReturn('AFTER1');
        $this->service->execute($this->claim($this->service->submit($id, $this->input(), 1)['task_id']));
        $this->customers[10]['buyer_return_logistics_company'] = '顺丰速运';
        $this->customers[10]['buyer_return_logistics_no'] = 'SF123';
        $this->service->changed(10, 'return_shipment');
        $taskId = (int) $this->repository->query('tasks')->where('type', 'after_sale_return')->value('id');
        $this->provider->expects(self::never())->method('shipReturn');
        try {
            $this->service->execute($this->claim($taskId));
            self::fail('Missing carrier code must stop transmission');
        } catch (\DomainException $error) {
            self::assertStringContainsString('return_carriers', $error->getMessage());
        }
        self::assertStringContainsString('return_carriers', $this->repository->require('after_sales', $id)['last_error']);
        self::assertSame('return_queued', $this->repository->require('after_sales', $id)['status']);
    }

    private function input(): array
    {
        return ['external_order_item_id' => '500', 'quantity' => 1, 'amount_cents' => 100, 'freight_cents' => 0,
            'technical_service_fee_cents' => 0, 'reason_type' => 1, 'reason' => '申请退款', 'description' => '',
            'received_status' => 0, 'refund_type' => 0, 'refund_way' => 1];
    }

    private function localId(int $customerId): int
    {
        return (int) $this->repository->query('after_sales')->where('after_sale_id', $customerId)->value('id');
    }

    private function claim(int $taskId): array
    {
        return $this->connection->transaction(fn () => $this->tasks->claim($taskId, 0)) ?? throw new \RuntimeException('Could not claim fixture task');
    }
}
