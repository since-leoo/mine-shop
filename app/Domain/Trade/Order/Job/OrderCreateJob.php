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

namespace App\Domain\Trade\Order\Job;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Event\OrderCreatedEvent;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderStockService;
use App\Domain\Trade\Order\Service\OrderPendingCacheService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class OrderCreateJob extends Job
{
    public int $maxAttempts = 3;

    public function __construct(
        protected string $tradeNo,
        protected array $entitySnapshot,
        protected array $itemsPayload,
        protected array $addressPayload,
        protected array $couponUserIds,
        protected string $orderType,
        protected string $stockHashKey,
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $repository = $container->get(OrderRepository::class);
        $strategyFactory = $container->get(OrderTypeStrategyFactory::class);
        $mallSettingService = $container->get(DomainMallSettingService::class);
        $pendingCache = $container->get(OrderPendingCacheService::class);
        $logger = $container->get(LoggerInterface::class);

        // 从快照重建 Entity
        $entity = $this->rebuildEntity($container);
        $entity->applySubmissionPolicy($mallSettingService->order());

        $strategy = $strategyFactory->make($this->orderType);

        // 策略自行恢复活动实体
        $strategy->rehydrate($entity, $container);

        // DB 事务：入库 + 后置逻辑（含优惠券核销）
        $entity = Db::transaction(static function () use ($entity, $strategy, $repository) {
            $savedEntity = $repository->save($entity);
            $strategy->postCreate($savedEntity);

            return $savedEntity;
        });

        // 标记成功
        $pendingCache->markCreated($this->tradeNo);
        event(new OrderCreatedEvent($entity));

        $logger->info('OrderCreateJob: 订单创建成功', [
            'trade_no' => $this->tradeNo,
            'order_type' => $this->orderType,
        ]);
    }

    /**
     * 最终失败回调：回滚 Redis 库存，标记订单失败.
     */
    public function fail(\Throwable $e): void
    {
        $container = ApplicationContext::getContainer();
        $stockService = $container->get(DomainOrderStockService::class);
        $pendingCache = $container->get(OrderPendingCacheService::class);
        $logger = $container->get(LoggerInterface::class);

        $stockService->rollback($this->itemsPayload, $this->stockHashKey);
        $pendingCache->markFailed($this->tradeNo, $e->getMessage());

        $logger->error('OrderCreateJob: 最终失败，已回滚库存', [
            'trade_no' => $this->tradeNo,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 从快照重建 OrderEntity.
     *
     * 秒杀/拼团订单需要重新加载活动实体，供 postCreate() 使用。
     */
    private function rebuildEntity(ContainerInterface $container): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->setOrderNo($this->tradeNo);
        $entity->setMemberId((int) $this->entitySnapshot['member_id']);
        $entity->setOrderType($this->entitySnapshot['order_type']);
        $entity->setBuyerRemark($this->entitySnapshot['buyer_remark'] ?? '');
        $entity->setGoodsAmount((int) $this->entitySnapshot['goods_amount']);
        $entity->setShippingFee((int) $this->entitySnapshot['shipping_fee']);
        $entity->setDiscountAmount((int) $this->entitySnapshot['discount_amount']);
        $entity->setTotalAmount((int) $this->entitySnapshot['total_amount']);
        $entity->setPayAmount((int) $this->entitySnapshot['pay_amount']);
        $entity->setCouponAmount((int) ($this->entitySnapshot['coupon_amount'] ?? 0));
        $entity->setAppliedCouponUserIds($this->couponUserIds);

        // 重建 items
        $entity->replaceItemsFromPayload($this->itemsPayload);

        // 重建地址
        if (! empty($this->addressPayload)) {
            $entity->useAddressPayload($this->addressPayload);
        }

        // 重建 extras（标量值）
        foreach ($this->entitySnapshot['extras'] ?? [] as $key => $value) {
            $entity->setExtra($key, $value);
        }

        return $entity;
    }
}
