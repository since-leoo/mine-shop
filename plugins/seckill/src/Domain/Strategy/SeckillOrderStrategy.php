<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Strategy;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use Plugin\Since\Seckill\Domain\Entity\SeckillProductEntity;
use Plugin\Since\Seckill\Domain\Repository\SeckillOrderRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillProductRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillSessionRepository;
use Plugin\Since\Seckill\Domain\Service\SeckillCacheService;
use Carbon\Carbon;

final class SeckillOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService,
        private readonly SeckillCacheService $cacheService,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository,
        private readonly SeckillOrderRepository $orderRepository,
    ) {}

    public function type(): string { return 'seckill'; }

    public function validate(OrderEntity $orderEntity): void
    {
        $sessionId = (int) $orderEntity->getExtra('session_id');
        if ($sessionId <= 0) { throw new \RuntimeException('缺少秒杀场次信息'); }
        if (empty($item = $orderEntity->getItems()[0] ?? null)) { throw new \RuntimeException('商品信息非法'); }

        $session = $this->cacheService->getSession($sessionId);
        if (!$session || !$session->canPurchase()) { throw new \RuntimeException('秒杀场次不存在或已结束'); }

        $seckillProduct = $this->loadSeckillProduct($orderEntity, $item->getSkuId(), $item->getQuantity());
        $purchased = $this->orderRepository->getMemberPurchasedQuantity($sessionId, $orderEntity->getMemberId(), $seckillProduct->getId());
        if (!$seckillProduct->canUserPurchase($item->getQuantity(), $purchased)) {
            throw new \RuntimeException(\sprintf('超出限购数量，每人限购 %d 件', $seckillProduct->getMaxQuantityPerUser()));
        }
        $orderEntity->setExtra('seckill_product_entity', $seckillProduct);
    }

    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $item = $orderEntity->getItems()[0];
        /** @var SeckillProductEntity $seckillProduct */
        $seckillProduct = $orderEntity->getExtra('seckill_product_entity');
        $snapshots = $this->snapshotService->getSkuSnapshots([$item->getSkuId()]);
        $snapshot = $snapshots[$item->getSkuId()] ?? null;
        if (!$snapshot) { throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId())); }
        $item->attachSnapshot($snapshot);
        $seckillPrice = $seckillProduct->getPrice()->getSeckillPrice();
        $item->setUnitPrice($seckillPrice);
        $item->setTotalPrice($seckillPrice * $item->getQuantity());
        $orderEntity->syncPriceDetailFromItems();
        $orderEntity->setPriceDetail($orderEntity->getPriceDetail() ?? new OrderPriceValue());
        return $orderEntity;
    }

    public function applyCoupon(OrderEntity $orderEntity, array $couponList): void
    {
        if (!empty($couponList)) { throw new \RuntimeException('秒杀订单不支持使用优惠券'); }
        $orderEntity->setCouponAmount(0);
    }

    public function adjustPrice(OrderEntity $orderEntity): void {}

    public function postCreate(OrderEntity $orderEntity): void
    {
        $item = $orderEntity->getItems()[0];
        /** @var SeckillProductEntity $seckillProduct */
        $seckillProduct = $orderEntity->getExtra('seckill_product_entity');
        $this->orderRepository->createOrder([
            'order_id' => $orderEntity->getId(), 'activity_id' => (int) $orderEntity->getExtra('activity_id'),
            'session_id' => (int) $orderEntity->getExtra('session_id'), 'seckill_product_id' => $seckillProduct->getId(),
            'member_id' => $orderEntity->getMemberId(), 'product_id' => $seckillProduct->getProductId(),
            'product_sku_id' => $item->getSkuId(), 'quantity' => $item->getQuantity(),
            'original_price' => $seckillProduct->getPrice()->getOriginalPrice(),
            'seckill_price' => $seckillProduct->getPrice()->getSeckillPrice(),
            'total_amount' => $item->getTotalPrice(), 'status' => 'pending', 'seckill_time' => Carbon::now(),
        ]);
        $sessionId = (int) $orderEntity->getExtra('session_id');
        $this->productRepository->incrementSoldQuantity($item->getSkuId(), $sessionId, $item->getQuantity());
        $this->sessionRepository->updateQuantityStats($sessionId);
    }

    private function loadSeckillProduct(OrderEntity $entity, int $skuId, int $quantity): SeckillProductEntity
    {
        $sessionId = (int) $entity->getExtra('session_id');
        $product = $this->cacheService->getProductBySkuId($sessionId, $skuId);
        if (!$product) { throw new \RuntimeException('该商品不在当前秒杀场次中'); }
        $session = $this->cacheService->getSession($sessionId);
        if (!$session || !$session->canPurchase()) { throw new \RuntimeException('秒杀场次不存在或已结束'); }
        if (!$product->canSell($quantity)) { throw new \RuntimeException('秒杀商品库存不足或已下架'); }
        return $product;
    }
}
