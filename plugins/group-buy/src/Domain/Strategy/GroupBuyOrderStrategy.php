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

namespace Plugin\Since\GroupBuy\Domain\Strategy;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use Plugin\Since\GroupBuy\Domain\Entity\GroupBuyEntity;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyOrderService;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyService;
use Psr\Container\ContainerInterface;

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

    public function validate(OrderEntity $orderEntity): OrderEntity
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

        $buyOriginal = (bool) $orderEntity->getExtra('buy_original_price');

        if ($buyOriginal) {
            // 原价购买：只需验证活动存在且 SKU 匹配，不校验拼团资格/库存
            $entity = $this->groupBuyOrderService->validateActivity(
                (int) $orderEntity->getExtra('group_buy_id'),
                $item->getSkuId(),
                $item->getQuantity(),
                $orderEntity->getMemberId(),
                null,
                true,
            );
        } else {
            $entity = $this->groupBuyOrderService->validateActivity(
                (int) $orderEntity->getExtra('group_buy_id'),
                $item->getSkuId(),
                $item->getQuantity(),
                $orderEntity->getMemberId(),
                $orderEntity->getExtra('group_no'),
            );
        }
        $orderEntity->setExtra('group_buy_entity', $entity);

        $snapshots = $this->snapshotService->getSkuSnapshots([$item->getSkuId()]);
        $snapshot = $snapshots[$item->getSkuId()] ?? null;
        if (! $snapshot) {
            throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId()));
        }
        $item->attachSnapshot($snapshot);

        return $orderEntity;
    }

    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $item = $orderEntity->getItems()[0];
        /** @var GroupBuyEntity $groupBuyEntity */
        $groupBuyEntity = $orderEntity->getExtra('group_buy_entity');

        // 原价购买：用户选择不参与拼团，按原价下单
        $buyOriginal = (bool) $orderEntity->getExtra('buy_original_price');
        $unitPrice = $buyOriginal ? $groupBuyEntity->getOriginalPrice() : $groupBuyEntity->getGroupPrice();

        $item->setUnitPrice($unitPrice);
        $item->setTotalPrice($unitPrice * $item->getQuantity());
        $orderEntity->syncPriceDetailFromItems();
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setDiscountAmount(0);
        $priceDetail->setShippingFee(0);
        $orderEntity->setPriceDetail($priceDetail);
        return $orderEntity;
    }

    public function applyFreight(OrderEntity $orderEntity): void
    {
        // 拼团订单免运费
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setShippingFee(0);
        $orderEntity->setPriceDetail($priceDetail);
    }

    public function applyCoupon(OrderEntity $orderEntity, ?int $couponId): void
    {
        // 拼团订单（含原价购买）均不允许使用优惠券
        if ($couponId !== null && $couponId > 0) {
            throw new \RuntimeException('拼团订单不支持使用优惠券');
        }
        $orderEntity->setCouponAmount(0);
    }

    public function rehydrate(OrderEntity $orderEntity, ContainerInterface $container): void
    {
        $groupBuyId = (int) ($orderEntity->getExtra('group_buy_id') ?? 0);
        if ($groupBuyId > 0) {
            $groupBuyService = $container->get(DomainGroupBuyService::class);
            $groupBuyEntity = $groupBuyService->getEntity($groupBuyId);
            $orderEntity->setExtra('group_buy_entity', $groupBuyEntity);
        }
    }

    public function postCreate(OrderEntity $orderEntity): void
    {
        // 原价购买不创建拼团订单记录
        if ((bool) $orderEntity->getExtra('buy_original_price')) {
            return;
        }

        /** @var GroupBuyEntity $groupBuyEntity */
        $groupBuyEntity = $orderEntity->getExtra('group_buy_entity');
        $this->groupBuyOrderService->createGroupBuyOrder($orderEntity, $groupBuyEntity);
    }
}
