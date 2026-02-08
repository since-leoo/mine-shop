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

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Marketing\Coupon\Service\DomainCouponUserService;
use App\Domain\Member\Service\DomainMemberAddressService;
use App\Domain\Trade\Order\Contract\OrderPreviewInput;
use App\Domain\Trade\Order\Contract\OrderSubmitInput;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Mapper\OrderMapper;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Order\Service\DomainOrderStockService;
use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use App\Domain\Trade\Shipping\Service\FreightCalculationService;
use App\Infrastructure\Abstract\IService;

/**
 * 面向 API 场景的订单写领域服务.
 *
 * 包含小程序端专属的订单操作逻辑（预览、提交、取消、确认收货）.
 * 公共方法（如 getEntity、update）仍调用 OrderService.
 */
final class DomainApiOrderCommandService extends IService
{
    public function __construct(
        public readonly OrderRepository $repository,
        private readonly DomainOrderService $orderService,
        private readonly OrderTypeStrategyFactory $strategyFactory,
        private readonly DomainOrderStockService $stockService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly DomainMemberAddressService $addressService,
        private readonly DomainCouponUserService $couponUserService,
        private readonly FreightCalculationService $freightCalculationService,
        private readonly ProductSnapshotInterface $snapshotService,
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
        $entity = $this->buildEntityFromInput($input);
        $entity->guardPreorderAllowed($this->mallSettingService->product()->allowPreorder());
        $strategy = $this->strategyFactory->make($entity->getOrderType());
        $strategy->validate($entity);
        $strategy->buildDraft($entity);
        $this->applyFreight($entity);
        $strategy->applyCoupon($entity, $input->getCouponList() ?? []);
        $strategy->adjustPrice($entity);

        return $entity;
    }

    /**
     * 提交订单.
     *
     * @throws \Throwable
     */
    public function submit(OrderSubmitInput $input): OrderEntity
    {
        // 构建订单
        $entity = $this->buildEntityFromInput($input);
        // 预订单检查
        $entity->guardPreorderAllowed($this->mallSettingService->product()->allowPreorder());
        // 订单过期时间设置为系统默认
        $entity->applySubmissionPolicy($this->mallSettingService->order());
        // 订单类型策略
        $strategy = $this->strategyFactory->make($entity->getOrderType());
        // 订单数据验证/构建验证商品/计算运费
        $strategy->validate($entity);
        $strategy->buildDraft($entity);
        $this->applyFreight($entity);
        // 优惠券使用
        $strategy->applyCoupon($entity, $input->getCouponList() ?? []);
        // 订单价格调整
        $strategy->adjustPrice($entity);
        // 订单价格验证是否变化
        $entity->verifyPrice($input->getTotalAmount());
        // 获取商品
        $items = array_map(static fn ($item) => $item->toArray(), $entity->getItems());
        // 根据订单类型决定库存 Hash Key
        $stockHashKey = $entity->getOrderType() === 'seckill'
            ? DomainOrderStockService::seckillStockKey((int) $entity->getExtra('session_id'))
            : 'product:stock';
        // 获取商品锁
        $locks = $this->stockService->acquireLocks($items, $stockHashKey);
        try {
            $this->stockService->reserve($items, $stockHashKey);
            try {
                $entity = $this->repository->save($entity);
                $this->markCouponsUsed($entity);
                $strategy->postCreate($entity);
            } catch (\Throwable $e) {
                $this->stockService->rollback($items, $stockHashKey);
                throw $e;
            }
        } finally {
            $this->stockService->releaseLocks($locks);
        }

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
     *
     * 遍历订单商品，按商品维度调用 FreightCalculationService 计算运费，
     * 将总运费写入 OrderPriceValue。
     */
    private function applyFreight(OrderEntity $entity): void
    {
        $address = $entity->getAddress();
        $province = $address?->getProvince() ?? '';

        $totalFreight = 0;
        $productFreightCache = [];

        foreach ($entity->getItems() as $item) {
            $productId = $item->getProductId();
            if ($productId <= 0) {
                continue;
            }

            // 同一商品只查询一次运费配置
            if (! isset($productFreightCache[$productId])) {
                $product = $this->snapshotService->getProduct($productId);
                $productFreightCache[$productId] = [
                    'freight_type' => (string) ($product['freight_type'] ?? 'default'),
                    'flat_freight_amount' => (int) ($product['flat_freight_amount'] ?? 0),
                    'shipping_template_id' => isset($product['shipping_template_id']) ? (int) $product['shipping_template_id'] : null,
                ];
            }

            $freightConfig = $productFreightCache[$productId];
            $totalFreight += $this->freightCalculationService->calculate(
                $freightConfig['freight_type'],
                $freightConfig['flat_freight_amount'],
                $freightConfig['shipping_template_id'],
                $province,
                $item->getQuantity(),
                (int) $item->getWeight(),
            );
        }

        $priceDetail = $entity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setShippingFee($totalFreight);
        $entity->setPriceDetail($priceDetail);
    }

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
     * 标记订单关联的优惠券为已使用.
     */
    private function markCouponsUsed(OrderEntity $entity): void
    {
        $couponUserIds = $entity->getAppliedCouponUserIds();
        if (empty($couponUserIds)) {
            return;
        }

        foreach ($couponUserIds as $couponUserId) {
            $couponUserEntity = $this->couponUserService->getEntity($couponUserId);
            $this->couponUserService->markUsed($couponUserEntity, $entity->getId());
        }
    }
}
