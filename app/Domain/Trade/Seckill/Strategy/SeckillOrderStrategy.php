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

namespace App\Domain\Trade\Seckill\Strategy;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Trade\Seckill\Service\SeckillCacheService;
use Carbon\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class SeckillOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService,
        private readonly SeckillCacheService $cacheService,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository,
        private readonly SeckillOrderRepository $orderRepository,
    ) {}

    /**
     * 订单类型.
     */
    public function type(): string
    {
        return 'seckill';
    }

    /**
     * 验证订单.
     */
    public function validate(OrderEntity $orderEntity): OrderEntity
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

        $seckillProduct = $this->loadSeckillProduct($orderEntity, $item->getSkuId(), $item->getQuantity());

        // 检测限购
        $purchased = $this->orderRepository->getMemberPurchasedQuantity(
            $sessionId,
            $orderEntity->getMemberId(),
            $seckillProduct->getId()
        );

        // 检测限购
        if (! $seckillProduct->canUserPurchase($item->getQuantity(), $purchased)) {
            throw new \RuntimeException(\sprintf('超出限购数量，每人限购 %d 件', $seckillProduct->getMaxQuantityPerUser()));
        }
        $orderEntity->setExtra('seckill_product_entity', $seckillProduct);

        // 获取商品快照
        $snapshots = $this->snapshotService->getSkuSnapshots([$item->getSkuId()]);
        $snapshot = $snapshots[$item->getSkuId()] ?? null;
        if (! $snapshot) {
            throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId()));
        }
        $item->attachSnapshot($snapshot);

        return $orderEntity;
    }

    /**
     * 构建订单价格数据.
     */
    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $item = $orderEntity->getItems()[0];
        /** @var SeckillProductEntity $seckillProduct */
        $seckillProduct = $orderEntity->getExtra('seckill_product_entity');

        // 设置商品价格
        $seckillPrice = $seckillProduct->getPrice()->getSeckillPrice();
        $item->setUnitPrice($seckillPrice);
        $item->setTotalPrice($seckillPrice * $item->getQuantity());
        $orderEntity->syncPriceDetailFromItems();
        $orderEntity->setPriceDetail($orderEntity->getPriceDetail() ?? new OrderPriceValue());
        return $orderEntity;
    }

    /**
     * 运费计算.
     */
    public function applyFreight(OrderEntity $orderEntity): void
    {
        // 秒杀订单免运费
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setShippingFee(0);
        $orderEntity->setPriceDetail($priceDetail);
    }

    /**
     * 秒杀订单不支持优惠券.
     */
    public function applyCoupon(OrderEntity $orderEntity, ?int $couponId): void {}

    /**
     * 活动数据上下文.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function rehydrate(OrderEntity $orderEntity, ContainerInterface $container): void
    {
        $sessionId = (int) ($orderEntity->getExtra('session_id') ?? 0);
        $items = $orderEntity->getItems();
        $skuId = $items[0]?->getSkuId() ?? 0;
        if ($sessionId > 0 && $skuId > 0) {
            $cacheService = $container->get(SeckillCacheService::class);
            $seckillProduct = $cacheService->getProductBySkuId($sessionId, $skuId);
            if ($seckillProduct) {
                $orderEntity->setExtra('seckill_product_entity', $seckillProduct);
            }
        }
    }

    /**
     * 订单创建后.
     */
    public function postCreate(OrderEntity $orderEntity): void
    {
        $item = $orderEntity->getItems()[0];
        /** @var SeckillProductEntity $seckillProduct */
        $seckillProduct = $orderEntity->getExtra('seckill_product_entity');

        // 创建秒杀订单
        $this->orderRepository->createOrder([
            'order_id' => $orderEntity->getId(), 'activity_id' => (int) $orderEntity->getExtra('activity_id'),
            'session_id' => (int) $orderEntity->getExtra('session_id'), 'seckill_product_id' => $seckillProduct->getId(),
            'member_id' => $orderEntity->getMemberId(), 'product_id' => $seckillProduct->getProductId(),
            'product_sku_id' => $item->getSkuId(), 'quantity' => $item->getQuantity(),
            'original_price' => $seckillProduct->getPrice()->getOriginalPrice(),
            'seckill_price' => $seckillProduct->getPrice()->getSeckillPrice(),
            'total_amount' => $item->getTotalPrice(), 'status' => 'pending', 'seckill_time' => Carbon::now(),
        ]);

        // 秒杀商品销量更新
        $sessionId = (int) $orderEntity->getExtra('session_id');
        $this->productRepository->incrementSoldQuantity($item->getSkuId(), $sessionId, $item->getQuantity());
        $this->sessionRepository->updateQuantityStats($sessionId);
    }

    /**
     * 加载秒杀商品
     */
    private function loadSeckillProduct(OrderEntity $entity, int $skuId, int $quantity): SeckillProductEntity
    {
        $sessionId = (int) $entity->getExtra('session_id');
        $product = $this->cacheService->getProductBySkuId($sessionId, $skuId);
        if (! $product) {
            throw new \RuntimeException('该商品不在当前秒杀场次中');
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
}
