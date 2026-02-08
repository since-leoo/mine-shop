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

namespace App\Domain\Trade\Order\Api\Command;

use App\Domain\Member\Service\DomainMemberAddressService;
use App\Domain\Trade\Order\Contract\OrderPreviewInput;
use App\Domain\Trade\Order\Contract\OrderSubmitInput;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Job\OrderCreateJob;
use App\Domain\Trade\Order\Mapper\OrderMapper;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Order\Service\DomainOrderStockService;
use App\Domain\Trade\Order\Service\OrderPendingCacheService;
use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Order\Order;
use Hyperf\AsyncQueue\Driver\DriverFactory;

/**
 * 面向 API 场景的订单写领域服务.
 *
 * 预览：同步构建 + 算价。
 * 提交：同步校验 + Lua 扣库存 + 投递队列，异步入库。
 * 取消/确认收货：同步操作。
 */
final class DomainApiOrderCommandService extends IService
{
    public function __construct(
        public readonly OrderRepository $repository,
        private readonly DomainOrderService $orderService,
        private readonly OrderTypeStrategyFactory $strategyFactory,
        private readonly DomainOrderStockService $stockService,
        private readonly DomainMemberAddressService $addressService,
        private readonly OrderPendingCacheService $pendingCacheService,
        private readonly DriverFactory $driverFactory,
    ) {}

    /**
     * 获取订单实体（公共方法，委托给 OrderService）.
     */
    public function getEntity(int $id = 0, string $orderNo = ''): OrderEntity
    {
        return $this->orderService->getEntity($id, $orderNo);
    }

    /**
     * 预览订单.
     */
    public function preview(OrderPreviewInput $input): OrderEntity
    {
        return $this->buildOrder($input);
    }

    /**
     * 提交订单（异步）.
     *
     * 同步阶段：校验 + 算价 + Lua 原子扣库存 + 投递队列。
     * 返回带 tradeNo 的 Entity（status=processing），前端轮询结果。
     */
    public function submit(OrderSubmitInput $input): OrderEntity
    {
        // 1. 构建 + 校验 + 算价
        $entity = $this->buildOrder($input);
        $activeId = $entity->getExtra('session_id') ?: $entity->getExtra('group_buy_id') ?: 0;
        $stockHashKey = DomainOrderStockService::resolveStockKey($entity->getOrderType(), (int) $activeId);

        // 2. Lua 原子扣库存（无需分布式锁）
        $items = array_map(static fn ($item) => $item->toArray(), $entity->getItems());
        $this->stockService->reserve($items, $stockHashKey);

        // 3. 生成 tradeNo，构建快照，写入 Redis pending 状态
        $tradeNo = Order::generateOrderNo();
        $entity->setOrderNo($tradeNo);
        $entitySnapshot = $this->buildSnapshot($entity);
        $addressPayload = $entity->getAddress()?->toArray() ?? [];

        // 缓存下单状态为pending
        $this->pendingCacheService->markProcessing($tradeNo, $entitySnapshot);

        // 4. 投递异步 Job
        $this->driverFactory->get('default')->push(new OrderCreateJob(
            tradeNo: $tradeNo,
            entitySnapshot: $entitySnapshot,
            itemsPayload: $items,
            addressPayload: $addressPayload,
            couponUserIds: $entity->getAppliedCouponUserIds(),
            orderType: $entity->getOrderType(),
            stockHashKey: $stockHashKey,
        ));

        return $entity;
    }

    /**
     * 取消订单.
     */
    public function cancel(OrderEntity $entity): OrderEntity
    {
        $this->repository->cancel($entity);

        return $entity;
    }

    /**
     * 确认收货.
     */
    public function confirmReceipt(OrderEntity $entity): OrderEntity
    {
        $entity->complete();
        $this->repository->complete($entity);

        return $entity;
    }

    /**
     * 查询异步下单结果.
     *
     * @return array{status: string, error: string}
     */
    public function getSubmitResult(string $tradeNo): array
    {
        return $this->pendingCacheService->getStatus($tradeNo);
    }

    /**
     * 公共订单构建流程：构建实体 → 策略校验 → 构建草稿 → 运费 → 优惠券.
     */
    private function buildOrder(OrderPreviewInput|OrderSubmitInput $input): OrderEntity
    {
        $entity = $this->buildEntityFromInput($input);
        $strategy = $this->strategyFactory->make($entity->getOrderType());
        $entity = $strategy->validate($entity);
        $strategy->buildDraft($entity);
        $strategy->applyFreight($entity);
        $strategy->applyCoupon($entity, $input->getCouponId());
        $input instanceof OrderSubmitInput && $entity->verifyPrice($input->getTotalAmount());
        return $entity;
    }

    /**
     * 从 Input 构建 Entity.
     */
    private function buildEntityFromInput(OrderPreviewInput $input): OrderEntity
    {
        $entity = OrderMapper::getNewEntity();
        $entity->initFromInput($input);
        $address = $this->resolveAddress($input);
        if ($address) {
            $entity->setAddress($address);
        }

        return $entity;
    }

    /**
     * 计算并设置订单运费.
     */

    /**
     * 解析用户地址：优先使用直接传入的地址，其次按 ID 查询，最后使用默认地址.
     */
    private function resolveAddress(OrderPreviewInput $input): ?OrderAddressValue
    {
        if ($input->getUserAddress()) {
            return OrderAddressValue::fromArray($input->getUserAddress());
        }
        if ($input->getAddressId()) {
            $detail = $this->addressService->detail($input->getMemberId(), $input->getAddressId());
            return OrderAddressValue::fromArray($detail);
        }
        $default = $this->addressService->default($input->getMemberId());
        return $default ? OrderAddressValue::fromArray($default) : null;
    }

    /**
     * 构建 Entity 快照（供异步 Job 重建上下文）.
     */
    private function buildSnapshot(OrderEntity $entity): array
    {
        $snapshot = $entity->toArray();
        $snapshot['coupon_amount'] = $entity->getCouponAmount();
        $snapshot['extras'] = array_filter(
            $entity->getExtras(),
            static fn ($v) => \is_scalar($v) || $v === null,
        );

        return $snapshot;
    }
}
