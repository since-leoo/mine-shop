<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Job;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Marketing\Coupon\Service\DomainCouponUserService;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyService;
use App\Domain\Marketing\Seckill\Service\SeckillCacheService;
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
        $couponUserService = $container->get(DomainCouponUserService::class);
        $pendingCache = $container->get(OrderPendingCacheService::class);
        $logger = $container->get(LoggerInterface::class);

        // 从快照重建 Entity
        $entity = $this->rebuildEntity($container);
        $entity->applySubmissionPolicy($mallSettingService->order());

        $strategy = $strategyFactory->make($this->orderType);

        // DB 事务：入库 + 优惠券 + 后置逻辑
        $entity = Db::transaction(function () use ($entity, $strategy, $couponUserService, $repository) {
            $savedEntity = $repository->save($entity);

            foreach ($this->couponUserIds as $couponUserId) {
                $couponUserEntity = $couponUserService->getEntity($couponUserId);
                $couponUserService->markUsed($couponUserEntity, $savedEntity->getId());
            }

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

        // 重新加载活动实体对象，供 postCreate() 使用
        $this->rehydrateActivityEntities($entity, $container);

        return $entity;
    }

    /**
     * 根据订单类型重新加载活动实体（秒杀/拼团）.
     *
     * validate() 阶段将 SeckillProductEntity / GroupBuyEntity 存入 extras，
     * 但这些对象无法序列化到 Job，所以在 Job 中根据标量 ID 重新加载。
     */
    private function rehydrateActivityEntities(OrderEntity $entity, ContainerInterface $container): void
    {
        if ($this->orderType === 'seckill') {
            $sessionId = (int) ($this->entitySnapshot['extras']['session_id'] ?? 0);
            $skuId = (int) ($this->itemsPayload[0]['sku_id'] ?? 0);
            if ($sessionId > 0 && $skuId > 0) {
                $cacheService = $container->get(SeckillCacheService::class);
                $seckillProduct = $cacheService->getProductBySkuId($sessionId, $skuId);
                if ($seckillProduct) {
                    $entity->setExtra('seckill_product_entity', $seckillProduct);
                }
            }
        }

        if ($this->orderType === 'group_buy') {
            $groupBuyId = (int) ($this->entitySnapshot['extras']['group_buy_id'] ?? 0);
            if ($groupBuyId > 0) {
                $groupBuyService = $container->get(DomainGroupBuyService::class);
                $groupBuyEntity = $groupBuyService->getEntity($groupBuyId);
                $entity->setExtra('group_buy_entity', $groupBuyEntity);
            }
        }
    }
}
