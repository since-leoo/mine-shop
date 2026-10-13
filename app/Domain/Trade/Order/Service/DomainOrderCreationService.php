<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Repository\OrderRepository;
use Psr\Container\ContainerInterface;

class DomainOrderCreationService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderTypeStrategyFactory $strategies,
        private readonly DomainMallSettingService $settings,
        private readonly ContainerInterface $container,
    ) {}

    public function create(string $tradeNo, array $payload): OrderEntity
    {
        $snapshot = $payload['snapshot'];
        $entity = new OrderEntity();
        $entity->setOrderNo($tradeNo);
        $entity->setMemberId((int) $snapshot['member_id']);
        $entity->setOrderType($snapshot['order_type']);
        $entity->setBuyerRemark($snapshot['buyer_remark'] ?? '');
        $entity->setGoodsAmount((int) $snapshot['goods_amount']);
        $entity->setShippingFee((int) $snapshot['shipping_fee']);
        $entity->setDiscountAmount((int) $snapshot['discount_amount']);
        $entity->setTotalAmount((int) $snapshot['total_amount']);
        $entity->setPayAmount((int) $snapshot['pay_amount']);
        $entity->setCouponAmount((int) ($snapshot['coupon_amount'] ?? 0));
        $entity->setAppliedCouponUserIds($payload['coupon_user_ids']);
        $entity->replaceItemsFromPayload($payload['items']);
        $entity->useAddressPayload($payload['address']);
        foreach ($snapshot['extras'] ?? [] as $key => $value) {
            $entity->setExtra($key, $value);
        }
        $entity->applySubmissionPolicy($this->settings->order());
        $strategy = $this->strategies->make($entity->getOrderType());
        $strategy->rehydrate($entity, $this->container);
        $this->orders->save($entity);
        $strategy->postCreate($entity);
        return $entity;
    }
}
