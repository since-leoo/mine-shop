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

namespace App\Domain\Trade\AfterSale\Service;

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use App\Domain\Trade\AfterSale\Mapper\AfterSaleMapper;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\OrderItem;
use DomainException;

final class DomainAfterSaleService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly AfterSaleRepository $afterSaleRepository,
    ) {}

    /**
     * @return array{can_apply: bool, types: array<int, string>, max_quantity: int, max_amount: int} 售后资格结果
     */
    public function eligibility(int $memberId, int $orderId, int $orderItemId): array
    {
        $orderItem = $this->resolveEligibleOrderItem($memberId, $orderId, $orderItemId);

        return [
            'can_apply' => true,
            'types' => $this->resolveAvailableTypes((string) $orderItem->order->status),
            'max_quantity' => (int) $orderItem->quantity,
            'max_amount' => (int) $orderItem->total_price,
        ];
    }

    public function apply(AfterSaleApplyInput $input): AfterSaleEntity
    {
        $this->resolveEligibleOrderItem($input->getMemberId(), $input->getOrderId(), $input->getOrderItemId());

        $entity = AfterSaleEntity::apply($input);
        $this->afterSaleRepository->createFromEntity($entity);

        return $entity;
    }

    public function saveEntity(AfterSaleEntity $entity): bool
    {
        return $this->afterSaleRepository->updateFromEntity($entity);
    }


    public function getEntity(int $id): AfterSaleEntity
    {
        /** @var AfterSale $model */
        $model = $this->afterSaleRepository->findById($id);
        if ($model === null) {
            throw new DomainException('获取售后单失败');
        }

        return AfterSaleMapper::fromModel($model);
    }

    private function resolveEligibleOrderItem(int $memberId, int $orderId, int $orderItemId): OrderItem
    {
        $orderItem = $this->orderRepository->findOrderItemForAfterSale($memberId, $orderId, $orderItemId);
        if ($orderItem === null) {
            throw new DomainException('当前订单商品不可申请售后');
        }

        if ($this->afterSaleRepository->findActiveByOrderItemId($orderItemId) !== null) {
            throw new DomainException('该订单商品已存在进行中的售后单');
        }

        $status = (string) $orderItem->order->status;
        if (! in_array($status, [OrderStatus::PAID->value, OrderStatus::PARTIAL_SHIPPED->value, OrderStatus::SHIPPED->value, OrderStatus::COMPLETED->value], true)) {
            throw new DomainException('当前订单状态不支持申请售后');
        }

        return $orderItem;
    }

    /**
     * @return array<int, string>
     */
    private function resolveAvailableTypes(string $orderStatus): array
    {
        if ($orderStatus === OrderStatus::PAID->value) {
            return [AfterSaleType::REFUND_ONLY->value];
        }

        return [
            AfterSaleType::REFUND_ONLY->value,
            AfterSaleType::RETURN_REFUND->value,
            AfterSaleType::EXCHANGE->value,
        ];
    }
}
