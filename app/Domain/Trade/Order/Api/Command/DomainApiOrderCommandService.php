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
     * 获取订单实体.
     *
     * 委托给 OrderService 获取订单实体，支持按 ID 或订单号查询。
     *
     * @param int $id 订单 ID（可选）
     * @param string $orderNo 订单号（可选）
     * @return OrderEntity 订单实体
     * @throws \RuntimeException 订单不存在时抛出
     */
    public function getEntity(int $id = 0, string $orderNo = ''): OrderEntity
    {
        return $this->orderService->getEntity($id, $orderNo);
    }

    /**
     * 预览订单.
     *
     * 根据用户选择的商品和地址，计算订单金额、运费、优惠等信息。
     * 不会实际创建订单，仅用于展示结算页面。
     *
     * @param OrderPreviewInput $input 预览输入参数
     * @return OrderEntity 包含计算结果的订单实体
     */
    public function preview(OrderPreviewInput $input): OrderEntity
    {
        return $this->buildOrder($input);
    }

    /**
     * 提交订单（异步）.
     *
     * 采用异步下单模式，流程如下：
     * 1. 同步阶段：校验参数 → 计算价格 → Lua 原子扣库存
     * 2. 异步阶段：投递 Job 到队列 → 异步入库
     *
     * 前端通过轮询 getSubmitResult 获取最终结果。
     *
     * @param OrderSubmitInput $input 提交输入参数
     * @return OrderEntity 包含 tradeNo 的订单实体（status=processing）
     * @throws \RuntimeException 库存不足或参数校验失败时抛出
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
     *
     * 将订单状态更新为已取消，需要在外部处理库存回滚。
     *
     * @param OrderEntity $entity 订单实体
     * @return OrderEntity 更新后的订单实体
     */
    public function cancel(OrderEntity $entity): OrderEntity
    {
        $this->repository->cancel($entity);

        return $entity;
    }

    /**
     * 确认收货.
     *
     * 将订单状态更新为已完成，触发后续的积分、佣金等结算流程。
     *
     * @param OrderEntity $entity 订单实体
     * @return OrderEntity 更新后的订单实体
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
     * 前端轮询此接口获取订单创建状态。
     *
     * @param string $tradeNo 订单号
     * @return array{status: string, error: string} 状态信息
     *         - status: processing（处理中）、created（成功）、failed（失败）
     *         - error: 失败原因（仅 failed 状态有值）
     */
    public function getSubmitResult(string $tradeNo): array
    {
        return $this->pendingCacheService->getStatus($tradeNo);
    }

    /**
     * 构建订单实体.
     *
     * 统一的订单构建流程：
     * 1. 从输入参数构建实体
     * 2. 根据订单类型获取策略（普通/秒杀/团购）
     * 3. 策略校验（库存、限购等）
     * 4. 构建订单草稿（商品信息、价格）
     * 5. 计算运费
     * 6. 应用优惠券
     *
     * @param OrderPreviewInput|OrderSubmitInput $input 输入参数
     * @return OrderEntity 构建完成的订单实体
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
     * 从输入参数构建订单实体.
     *
     * @param OrderPreviewInput $input 输入参数
     * @return OrderEntity 初始化后的订单实体
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
     * 解析用户收货地址.
     *
     * 地址解析优先级：
     * 1. 直接传入的地址信息（user_address）
     * 2. 按地址 ID 查询
     * 3. 使用用户默认地址
     *
     * @param OrderPreviewInput $input 输入参数
     * @return OrderAddressValue|null 地址值对象，无地址时返回 null
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
     * 构建订单快照.
     *
     * 将订单实体序列化为数组，供异步 Job 重建上下文使用。
     * 过滤掉非标量的 extras 字段，避免序列化问题。
     *
     * @param OrderEntity $entity 订单实体
     * @return array 订单快照数据
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
