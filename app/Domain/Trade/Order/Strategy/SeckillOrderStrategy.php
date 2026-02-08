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
use App\Domain\Marketing\Seckill\Entity\SeckillProductEntity;
use App\Domain\Marketing\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Marketing\Seckill\Repository\SeckillOrderRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillProductRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Marketing\Seckill\Service\SeckillCacheService;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use Carbon\Carbon;

/**
 * 秒杀订单策略.
 *
 * 秒杀订单特点：
 * - 只允许单个 SKU
 * - 使用秒杀价替代原价
 * - 不支持优惠券
 * - 限购校验（每人每场次限购）
 * - 下单后写入 seckill_session_orders 记录
 */
final class SeckillOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService,
        private readonly SeckillCacheService $cacheService,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository,
        private readonly SeckillOrderRepository $orderRepository,
    ) {}

    public function type(): string
    {
        return 'seckill';
    }

    public function validate(OrderEntity $orderEntity): void
    {
        $sessionId = (int) $orderEntity->getExtra('session_id');

        if ($sessionId <= 0) {
            throw new \RuntimeException('缺少秒杀场次信息');
        }

        if (empty($item = $orderEntity->getItems()[0] ?? null)) {
            throw new \RuntimeException('商品信息非法');
        }

        $session = $this->cacheService->getSession($sessionId);
        if (! $session || ! $session->canPurchase()) {
            throw new \RuntimeException('秒杀场次不存在或已结束');
        }

        // 加载秒杀商品
        $seckillProduct = $this->loadSeckillProduct($orderEntity, $item->getSkuId(), $item->getQuantity());
        // 限购校验（必须实时查库）
        $purchased = $this->getMemberPurchasedQuantity($sessionId, $orderEntity->getMemberId(), $seckillProduct->getId());

        if (! $seckillProduct->canUserPurchase($item->getQuantity(), $purchased)) {
            throw new \RuntimeException(\sprintf(
                '超出限购数量，每人限购 %d 件',
                $seckillProduct->getMaxQuantityPerUser()
            ));
        }

        $orderEntity->setExtra('seckill_product_entity', $seckillProduct);
    }

    /**
     * 构建订单草稿.
     */
    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $item = $orderEntity->getItems()[0];
        /** @var SeckillProductEntity $seckillProduct */
        $seckillProduct = $orderEntity->getExtra('seckill_product_entity');

        $snapshots = $this->snapshotService->getSkuSnapshots([$item->getSkuId()]);
        $snapshot = $snapshots[$item->getSkuId()] ?? null;
        if (! $snapshot) {
            throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId()));
        }

        $item->attachSnapshot($snapshot);

        // 用秒杀价覆盖原价
        $seckillPrice = $seckillProduct->getPrice()->getSeckillPrice();
        $item->setUnitPrice($seckillPrice);
        $item->setTotalPrice($seckillPrice * $item->getQuantity());

        // 同步价格详情
        $orderEntity->syncPriceDetailFromItems();
        $orderEntity->setPriceDetail($orderEntity->getPriceDetail() ?? new OrderPriceValue());

        return $orderEntity;
    }

    /**
     * 秒杀订单不支持优惠券.
     */
    public function applyCoupon(OrderEntity $orderEntity, array $couponList): void
    {
        if (! empty($couponList)) {
            throw new \RuntimeException('秒杀订单不支持使用优惠券');
        }
        $orderEntity->setCouponAmount(0);
    }

    public function adjustPrice(OrderEntity $orderEntity): void
    {
        // 秒杀订单不做额外价格调整
    }

    /**
     * 下单成功后写入秒杀订单记录，更新场次库存统计.
     */
    public function postCreate(OrderEntity $orderEntity): void
    {
        $item = $orderEntity->getItems()[0];
        /** @var SeckillProductEntity $seckillProduct */
        $seckillProduct = $orderEntity->getExtra('seckill_product_entity');

        $this->orderRepository->createOrder([
            'order_id' => $orderEntity->getId(),
            'activity_id' => (int) $orderEntity->getExtra('activity_id'),
            'session_id' => (int) $orderEntity->getExtra('session_id'),
            'seckill_product_id' => $seckillProduct->getId(),
            'member_id' => $orderEntity->getMemberId(),
            'product_id' => $seckillProduct->getProductId(),
            'product_sku_id' => $item->getSkuId(),
            'quantity' => $item->getQuantity(),
            'original_price' => $seckillProduct->getPrice()->getOriginalPrice(),
            'seckill_price' => $seckillProduct->getPrice()->getSeckillPrice(),
            'total_amount' => $item->getTotalPrice(),
            'status' => 'pending',
            'seckill_time' => Carbon::now(),
        ]);

        $sessionId = (int) $orderEntity->getExtra('session_id');

        // 更新秒杀商品已售数量
        $this->productRepository->incrementSoldQuantity($item->getSkuId(), $sessionId, $item->getQuantity());
        // 汇总到场次
        $this->sessionRepository->updateQuantityStats($sessionId);
    }

    private function loadSeckillProduct(OrderEntity $entity, int $skuId, int $quantity): SeckillProductEntity
    {
        $sessionId = (int) $entity->getExtra('session_id');

        $product = $this->cacheService->getProductBySkuId($sessionId, $skuId);
        if (! $product) {
            throw new \RuntimeException('该商品不在当前秒杀场次中');
        }

        if ($sessionId <= 0) {
            throw new \RuntimeException('缺少秒杀场次信息');
        }

        $session = $this->cacheService->getSession($sessionId);
        if (! $session || ! $session->canPurchase()) {
            throw new \RuntimeException('秒杀场次不存在或已结束');
        }

        if (! $product->canSell($quantity)) {
            throw new \RuntimeException('秒杀商品库存不足或已下架');
        }

        return $product;
    }

    /**
     * 获取会员已购数量
     *
     * @param int $sessionId
     * @param int $memberId
     * @param int $seckillProductId
     * @return int
     */
    private function getMemberPurchasedQuantity(int $sessionId, int $memberId, int $seckillProductId): int
    {
        return $this->orderRepository->getMemberPurchasedQuantity($sessionId, $memberId, $seckillProductId);
    }
}
