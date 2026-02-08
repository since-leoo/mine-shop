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

namespace App\Domain\Trade\Order\Strategy;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Marketing\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyOrderService;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;

/**
 * 拼团订单策略.
 *
 * 拼团订单特点：
 * - 只允许单个 SKU
 * - 使用团购价替代原价
 * - 不支持优惠券
 * - 运费和折扣均为 0
 * - 下单后写入 group_buy_orders 记录
 */
final class GroupBuyOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService,
        private readonly DomainGroupBuyOrderService $groupBuyOrderService,
    ) {}

    public function type(): string
    {
        return 'group_buy';
    }

    public function validate(OrderEntity $orderEntity): void
    {
        // 1. 登录检查
        if ($orderEntity->getMemberId() <= 0) {
            throw new \RuntimeException('请先登录后再下单');
        }

        // 2. 商品列表非空且只有一个 SKU
        $items = $orderEntity->getItems();
        if (empty($items)) {
            throw new \RuntimeException('至少选择一件商品');
        }
        if (\count($items) > 1) {
            throw new \RuntimeException('拼团订单仅支持单个商品');
        }

        // 3. 委托 DomainGroupBuyOrderService 校验活动
        $item = $items[0];
        $entity = $this->groupBuyOrderService->validateActivity(
            (int) $orderEntity->getExtra('group_buy_id'),
            $item->getSkuId(),
            $item->getQuantity(),
            $orderEntity->getMemberId(),
            $orderEntity->getExtra('group_no'),
        );

        // 4. 将 GroupBuyEntity 存入 extras 供后续使用
        $orderEntity->setExtra('group_buy_entity', $entity);
    }

    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $item = $orderEntity->getItems()[0];
        /** @var GroupBuyEntity $groupBuyEntity */
        $groupBuyEntity = $orderEntity->getExtra('group_buy_entity');

        // 1. 获取商品快照 → attachSnapshot
        $snapshots = $this->snapshotService->getSkuSnapshots([$item->getSkuId()]);
        $snapshot = $snapshots[$item->getSkuId()] ?? null;
        if (! $snapshot) {
            throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId()));
        }
        $item->attachSnapshot($snapshot);

        // 2. 用 groupPrice 覆盖 item 单价
        $groupPrice = $groupBuyEntity->getGroupPrice();
        $item->setUnitPrice($groupPrice);

        // 3. item 总价 = groupPrice * quantity
        $item->setTotalPrice($groupPrice * $item->getQuantity());

        // 4. syncPriceDetailFromItems()
        $orderEntity->syncPriceDetailFromItems();

        // 5. discountAmount = 0, shippingFee = 0
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setDiscountAmount(0);
        $priceDetail->setShippingFee(0);
        $orderEntity->setPriceDetail($priceDetail);

        return $orderEntity;
    }

    /**
     * 拼团订单不支持优惠券.
     */
    public function applyCoupon(OrderEntity $orderEntity, array $couponList): void
    {
        if (! empty($couponList)) {
            throw new \RuntimeException('拼团订单不支持使用优惠券');
        }
        $orderEntity->setCouponAmount(0);
    }

    public function adjustPrice(OrderEntity $orderEntity): void
    {
        // 拼团订单不做额外价格调整
    }

    /**
     * 下单成功后委托 DomainGroupBuyOrderService 创建拼团订单记录.
     */
    public function postCreate(OrderEntity $orderEntity): void
    {
        /** @var GroupBuyEntity $groupBuyEntity */
        $groupBuyEntity = $orderEntity->getExtra('group_buy_entity');

        $this->groupBuyOrderService->createGroupBuyOrder($orderEntity, $groupBuyEntity);
    }
}
