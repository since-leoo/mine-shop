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

namespace Plugin\SupplyChain\Application\Commad;

use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use Plugin\SupplyChain\Domain\Service\DomainSupplyProductService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyPurchaseService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyWorkflowService;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

final class AppSupplyWorkflow
{
    public function __construct(private readonly TransactionManagerInterface $transactions, private readonly SupplyChainManagerInterface $clients, private readonly DomainSupplyProductService $products, private readonly DomainSupplyPurchaseService $purchases, private readonly DomainSupplyWorkflowService $workflow, private readonly AppSupplyAfterSaleService $afterSales) {}

    public function execute(array $task): array
    {
        $type = $task['type'];
        $connectionId = (int) $task['connection_id'];
        $payload = $task['payload'];
        if ($type === 'order_release_protection') {
            // Local compensation remains runnable after the supplier connection is disabled.
            return $this->transactions->run(function () use ($task): array {
                $this->workflow->assertTaskOwner($task);
                return $this->purchases->releaseProtection((int) $task['id']);
            });
        }
        $this->workflow->connection($connectionId, $type);
        if ($type === 'connection_refresh') {
            return $this->transactions->run(fn () => $this->workflow->refresh($task));
        }
        if (str_starts_with($type, 'after_sale_')) {
            return $this->afterSales->execute($task);
        }
        try {
            $client = $this->clients->forConnection($connectionId);
        } catch (\DomainException|\InvalidArgumentException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new \RuntimeException('供应链接口尚未发送：' . SafeLogVo::error($error), 10002, $error);
        }
        if (\in_array($type, ['import', 'sync'], true)) {
            // A durable attempt sequence is shared by every worker, unlike machine wall clocks.
            $version = (int) ($task['attempt_id'] ?? 0);
            if ($version < 1) {
                throw new \DomainException('同步任务缺少持久化执行序号');
            }
            try {
                if ($type === 'import') {
                    $client->selection()->add([$payload['external_product_id']]);
                }
                $details = $client->catalog()->details([$payload['external_product_id']]);
                $product = null;
                foreach ($details as $detail) {
                    if ($detail['external_product_id'] === $payload['external_product_id']) {
                        $product = $detail;
                        break;
                    }
                }
                if ($product === null) {
                    throw new \DomainException('上游未返回所选商品');
                }
                $result = $this->transactions->run(fn () => $this->products->persist($connectionId, $product, $payload, $version));
                $this->products->project($result);
                return ['product_id' => $result['product_id'], 'link_id' => $result['link_id'], 'skipped' => $result['skipped'] ?? null];
            } catch (\Throwable $error) {
                $this->transactions->run(fn () => $this->workflow->productFailure($task, $error));
                throw $error;
            }
        }
        if ($type === 'messages') {
            $messages = $client->messages()->pull(100);
            $ids = $this->transactions->run(fn () => $this->workflow->receivedMessages($connectionId, $messages));
            if ($ids !== []) {
                $client->messages()->acknowledge($ids);
                $this->transactions->run(fn () => $this->workflow->acknowledgeMessages($connectionId, $ids));
            }
            return ['received' => \count($ids)];
        }
        $purchaseId = (int) ($payload['purchase_id'] ?? 0);
        $purchase = $this->workflow->purchase($purchaseId);
        if ($type === 'order_push') {
            if (\in_array($purchase['status'], ['accepted', 'completed'], true)) {
                $this->purchases->acknowledge($purchaseId);
                $this->transactions->run(fn () => $this->purchases->queryTask($purchaseId));
                return ['purchase_id' => $purchaseId, 'skipped' => 'already_accepted'];
            }
            if (\in_array($purchase['status'], ['submitting', 'unknown'], true)) {
                $this->transactions->run(fn () => $this->purchases->queryTask($purchaseId));
                throw new \RuntimeException('采购结果需查单确认，禁止重复提交', 10001);
            }
            // Reads and quote checks happen before acquiring the local submission state.
            try {
                $command = $this->workflow->command($purchase);
                $ids = array_values(array_unique(array_column(array_column($purchase['snapshot']['items'], 'snapshot'), 'external_product_id')));
                $details = $client->catalog()->details($ids);
                $client->quotes()->assertAvailable($command);
                $quote = $client->quotes()->quote($command);
                $this->workflow->assertQuote($purchase, $details, $quote);
                $this->transactions->run(function () use ($task, $purchaseId): void {
                    $this->workflow->assertTaskOwner($task);
                    $this->purchases->submitting($purchaseId);
                });
            } catch (\DomainException|\InvalidArgumentException $error) {
                throw $error;
            } catch (\Throwable $error) {
                throw new \RuntimeException('采购尚未发送：前置校验失败，' . SafeLogVo::error($error), 10002, $error);
            }
            try {
                // If a refund won the race and released this reservation, protect rejects before HTTP.
                try {
                    $this->purchases->protect($purchaseId);
                } catch (\Throwable $protectionError) {
                    throw new \RuntimeException('采购尚未发送：库存提交保护失败，' . SafeLogVo::error($protectionError), 10002, $protectionError);
                }
                try {
                    $this->transactions->run(fn () => $this->workflow->assertTaskOwner($task));
                } catch (\Throwable $leaseError) {
                    throw new \RuntimeException('采购尚未发送：任务执行租约已失效', 10002, $leaseError);
                }
                $result = $client->orders()->submit($command);
                $this->transactions->run(function () use ($task, $purchaseId, $result): void {
                    try {
                        $this->workflow->assertTaskOwner($task);
                    } catch (\Throwable $leaseError) {
                        throw new \RuntimeException('采购已发送但返回时租约失效，必须查单确认', 10001, $leaseError);
                    }
                    $this->purchases->accept($purchaseId, array_map(static fn ($number) => ['external_order_no' => $number, 'items' => []], $result['external_order_numbers']));
                });
            } catch (\Throwable $error) {
                $cleanupId = $this->transactions->run(fn () => $this->purchases->failure($purchaseId, $error, $task));
                if ($cleanupId !== null) {
                    try {
                        $this->transactions->run(fn () => $this->purchases->releaseProtection($cleanupId));
                    } catch (\Throwable $cleanupError) {
                        try {
                            $this->transactions->run(fn () => $this->purchases->recordProtectionReleaseFailure($cleanupId, $cleanupError));
                        } catch (\Throwable) {
                            // The compensation was already committed. Even a failed audit write
                            // must not replace the original known/unsent supplier classification.
                        }
                    }
                }
                throw $error;
            }
            $this->purchases->acknowledge($purchaseId);
            $this->transactions->run(fn () => $this->purchases->queryTask($purchaseId));
            return ['purchase_id' => $purchaseId] + $result;
        }
        if ($type === 'order_query') {
            $result = $client->orders()->findByMerchantOrderNo($purchase['merchant_order_no']);
            if (empty($result['orders'])) {
                throw new \RuntimeException('上游暂未查到采购单，继续查询，不重新采购');
            }
            $this->transactions->run(function () use ($purchaseId, $result): void {
                $this->purchases->accept($purchaseId, $result['orders']);
                $this->workflow->resolvePush($purchaseId);
            });
            $this->purchases->acknowledge($purchaseId);
            $packages = $client->orders()->logistics($purchase['merchant_order_no']);
            foreach ($packages as $package) {
                $this->transactions->run(fn () => $this->purchases->applyShipment($purchaseId, $purchase['merchant_order_no'], $package));
            }
            return ['purchase_id' => $purchaseId, 'orders' => \count($result['orders']), 'packages' => \count($packages)];
        }
        if ($type === 'order_cancel') {
            $this->transactions->run(fn () => $this->workflow->assertTaskOwner($task));
            $client->orders()->cancel($purchase['merchant_order_no']);
            return ['purchase_id' => $purchaseId];
        }
        if ($type === 'order_complete') {
            if ($purchase['status'] === 'completed') {
                return ['purchase_id' => $purchaseId, 'skipped' => 'already_completed'];
            }
            $this->purchases->assertCompletable($purchaseId);
            $this->transactions->run(fn () => $this->workflow->assertTaskOwner($task));
            $client->orders()->receive($purchase['merchant_order_no']);
            $this->transactions->run(fn () => $this->purchases->complete($purchaseId));
            return ['purchase_id' => $purchaseId];
        }
        throw new \DomainException('未知供应链任务类型');
    }
}
