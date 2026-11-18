<?php

declare(strict_types=1);

namespace App\Application\Api\Order;

use App\Domain\Trade\Order\Contract\OrderJobQueueInterface;

use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderCreationService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Infrastructure\Error\OrderError;
use Psr\Log\LoggerInterface;

final class AppOrderSubmissionService
{
    public const PUBLIC_ERROR_PREFIX = 'public:v1:';

    public function __construct(
        private readonly OrderSubmissionStoreInterface $submissions,
        private readonly StockReservationInterface $stock,
        private readonly OrderJobQueueInterface $queue,
        private readonly OrderRepository $orders,
        private readonly DomainOrderCreationService $creation,
        private readonly TransactionManagerInterface $transactions,
        private readonly TradeOutboxInterface $outbox,
        private readonly LoggerInterface $logger,
    ) {}

    public function submit(string $tradeNo, int $memberId, array $payload): void
    {
        $preparing = new \App\Domain\Trade\Order\Event\OrderSubmissionPreparing($memberId, $payload);
        event($preparing);
        $payload = $preparing->payload;
        // 先有持久记录，任何后续中断都能由扫描器恢复。库存使用该单号幂等预占。
        $this->submissions->create($tradeNo, $memberId, $payload);
        try {
            $this->transactions->run(function () use ($tradeNo, $payload): void {
                $row = $this->submissions->lock($tradeNo);
                if ($row['status'] !== 'pending') {
                    return;
                }
                $this->stock->reserve($tradeNo, $payload['items'], $payload['stock_key']);
                $this->submissions->updateStatus($tradeNo, 'reserved');
            });
        } catch (\DomainException $e) {
            $this->fail($tradeNo, $e);
            throw $e;
        } catch (\Throwable $e) {
            // 已持久化的提交仍返回处理中，避免客户端因基础设施超时重复创建新订单。
            $this->logger->warning('库存预占结果待恢复', ['trade_no' => $tradeNo, 'error' => $e->getMessage()]);
        }
        try {
            if (! $this->queue->push($tradeNo)) {
                $this->logger->warning('订单投递失败，等待持久化扫描恢复', ['trade_no' => $tradeNo]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('订单投递异常，等待持久化扫描恢复', ['trade_no' => $tradeNo, 'error' => $e->getMessage()]);
        }
    }

    public function process(string $tradeNo, ?array $legacyPayload = null): void
    {
        if ($legacyPayload !== null && $this->submissions->find($tradeNo) === null) {
            $legacyPayload['legacy'] = true;
            $this->submissions->create($tradeNo, (int) $legacyPayload['snapshot']['member_id'], $legacyPayload);
        }
        $this->transactions->run(function () use ($tradeNo): void {
            $row = $this->submissions->lock($tradeNo);
            if ($row === null || in_array($row['status'], ['created', 'failed'], true)) {
                return;
            }
            // 同一提交行锁覆盖数据库写入与状态变更，重复投递不会重复扣减或创建。
            $existing = $this->orders->findExistingByOrderNo($tradeNo);
            if ($existing === null) {
                $payload = $row['payload'];
                if (empty($payload['legacy'])) {
                    $this->stock->reserve($tradeNo, $payload['items'], $payload['stock_key']);
                }
                $this->creation->create($tradeNo, $payload);
                event(new \App\Domain\Trade\Order\Event\OrderPersisted($tradeNo, $payload));
            }
            $this->submissions->updateStatus($tradeNo, 'created');
            $this->outbox->append('created:' . $tradeNo, 'order.created', [
                'order_no' => $tradeNo, 'from_cart' => (bool) ($row['payload']['snapshot']['extras']['from_cart'] ?? false),
            ]);
        });
    }

    public function fail(string $tradeNo, \Throwable $exception): void
    {
        $this->logger->error('订单创建失败', ['trade_no' => $tradeNo, 'exception' => $exception]);
        $error = in_array($exception::class, [\DomainException::class, \InvalidArgumentException::class], true)
            ? $exception->getMessage()
            : OrderError::SUBMISSION_FAILED->message();
        $this->transactions->run(function () use ($tradeNo, $error): void {
            $row = $this->submissions->lock($tradeNo);
            if ($row === null || $row['status'] === 'created') {
                return;
            }
            if ($this->orders->findExistingByOrderNo($tradeNo) !== null) {
                $this->submissions->updateStatus($tradeNo, 'created');
                return;
            }
            $this->submissions->updateStatus($tradeNo, 'failed', self::PUBLIC_ERROR_PREFIX . $error);
            $this->outbox->append('release:' . $tradeNo, 'stock.release', [
                'trade_no' => $tradeNo, 'items' => $row['payload']['items'],
                'stock_key' => $row['payload']['stock_key'], 'legacy' => $row['payload']['legacy'] ?? false,
            ]);
        });
    }
}
