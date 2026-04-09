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

        $entity = $this->rebuildEntity($container);
        $entity->applySubmissionPolicy($mallSettingService->order());

        $strategy = $strategyFactory->make($this->orderType);
        $strategy->rehydrate($entity, $container);

        $entity = Db::transaction(static function () use ($entity, $strategy, $repository) {
            $savedEntity = $repository->save($entity);
            $strategy->postCreate($savedEntity);

            return $savedEntity;
        });

        $pendingCache->markCreated($this->tradeNo);
        event(new OrderCreatedEvent($entity));

        logger()->info('OrderCreateJob: order created', [
            'trade_no' => $this->tradeNo,
            'order_type' => $this->orderType,
        ]);
    }

    public function fail(\Throwable $e): void
    {
        $container = ApplicationContext::getContainer();
        $stockService = $container->get(DomainOrderStockService::class);
        $pendingCache = $container->get(OrderPendingCacheService::class);

        $stockService->rollback($this->itemsPayload, $this->stockHashKey);
        $pendingCache->markFailed($this->tradeNo, $e->getMessage());

        logger()->error('OrderCreateJob: order create failed', [
            'trade_no' => $this->tradeNo,
            'error' => $e->getMessage(),
        ]);
    }

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

        $entity->replaceItemsFromPayload($this->itemsPayload);

        if (! empty($this->addressPayload)) {
            $entity->useAddressPayload($this->addressPayload);
        }

        foreach ($this->entitySnapshot['extras'] ?? [] as $key => $value) {
            $entity->setExtra($key, $value);
        }

        return $entity;
    }
}
