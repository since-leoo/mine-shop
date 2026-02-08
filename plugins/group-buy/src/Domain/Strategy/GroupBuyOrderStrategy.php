<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Strategy;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use Plugin\Since\GroupBuy\Domain\Entity\GroupBuyEntity;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyOrderService;

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
        if ($orderEntity->getMemberId() <= 0) {
            throw new \RuntimeException('请先登录后再下单');
        }
        $items = $orderEntity->getItems();
        if (empty($items)) {
            throw new \RuntimeException('至少选择一件商品');
        }
        if (\count($items) > 1) {
            throw new \RuntimeException('拼团订单仅支持单个商品');
        }
        $item = $items[0];
        $entity = $this->groupBuyOrderService->validateActivity(
            (int) $orderEntity->getExtra('group_buy_id'),
            $item->getSkuId(),
            $item->getQuantity(),
            $orderEntity->getMemberId(),
            $orderEntity->getExtra('group_no'),
        );
        $orderEntity->setExtra('group_buy_entity', $entity);
    }

    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $item = $orderEntity->getItems()[0];
        /** @var GroupBuyEntity $groupBuyEntity */
        $groupBuyEntity = $orderEntity->getExtra('group_buy_entity');
        $snapshots = $this->snapshotService->getSkuSnapshots([$item->getSkuId()]);
        $snapshot = $snapshots[$item->getSkuId()] ?? null;
        if (! $snapshot) {
            throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId()));
        }
        $item->attachSnapshot($snapshot);
        $groupPrice = $groupBuyEntity->getGroupPrice();
        $item->setUnitPrice($groupPrice);
        $item->setTotalPrice($groupPrice * $item->getQuantity());
        $orderEntity->syncPriceDetailFromItems();
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setDiscountAmount(0);
        $priceDetail->setShippingFee(0);
        $orderEntity->setPriceDetail($priceDetail);
        return $orderEntity;
    }

    public function applyCoupon(OrderEntity $orderEntity, array $couponList): void
    {
        if (! empty($couponList)) {
            throw new \RuntimeException('拼团订单不支持使用优惠券');
        }
        $orderEntity->setCouponAmount(0);
    }

    public function adjustPrice(OrderEntity $orderEntity): void {}

    public function postCreate(OrderEntity $orderEntity): void
    {
        /** @var GroupBuyEntity $groupBuyEntity */
        $groupBuyEntity = $orderEntity->getExtra('group_buy_entity');
        $this->groupBuyOrderService->createGroupBuyOrder($orderEntity, $groupBuyEntity);
    }
}
