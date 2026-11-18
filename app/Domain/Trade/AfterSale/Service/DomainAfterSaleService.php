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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use App\Domain\Trade\AfterSale\Mapper\AfterSaleMapper;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Error\AfterSaleError;
use App\Infrastructure\Error\OrderError;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\OrderItem;

final class DomainAfterSaleService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly AfterSaleRepository $afterSaleRepository,
        private readonly DomainMallSettingService $mallSettingService,
    ) {}

    /**
     * @return array{can_apply: bool, types: array<int, string>, max_quantity: int, max_amount: int} 售后资格结果
     */
    public function eligibility(int $memberId, int $orderId, int $orderItemId): array
    {
        $orderItem = $this->resolveEligibleOrderItem($memberId, $orderId, $orderItemId);

        $allowance = $this->allowance($orderItem);
        return [
            'can_apply' => $allowance->quantity > 0,
            'types' => $this->resolveAvailableTypes((string) $orderItem->order->status),
            'max_quantity' => $allowance->quantity,
            'max_amount' => $allowance->amount,
        ];
    }

    public function apply(AfterSaleApplyInput $input): AfterSaleEntity
    {
        $item = $this->resolveEligibleOrderItem($input->getMemberId(), $input->getOrderId(), $input->getOrderItemId());
        $this->allowance($item)->assertRequest($input->getQuantity(), $input->getApplyAmount());
        if (! in_array($input->getType(), $this->resolveAvailableTypes((string) $item->order->status), true)) {
            throw AfterSaleError::ORDER_TYPE_UNSUPPORTED->exception();
        }
        $entity = AfterSaleEntity::apply($input);
        $this->afterSaleRepository->createFromEntity($entity);

        return $entity;
    }

    public function lockOrder(int $orderId): void
    {
        if ($this->orderRepository->findByIdForLock($orderId) === null) { throw OrderError::NOT_FOUND->exception(); }
    }

    public function cancelForMember(int $memberId, int $id): void
    {
        $model = $this->afterSaleRepository->findByIdAndMember($id, $memberId);
        $this->lockOrder((int) $model->order_id);
        $entity = $this->getLockedEntity($id);
        $entity->cancel();
        $this->saveEntity($entity);
    }

    public function submitMemberReturn(\App\Domain\Trade\AfterSale\Contract\AfterSaleReturnShipmentInput $input): void
    {
        $model = $this->afterSaleRepository->findByIdAndMember($input->getId(), $input->getMemberId());
        $this->lockOrder((int) $model->order_id);
        $entity = $this->getLockedEntity($input->getId());
        $entity->submitBuyerReturn($input->getLogisticsCompany(), $input->getLogisticsNo());
        $this->saveEntity($entity);
    }

    private function allowance(OrderItem $item): \App\Domain\Trade\AfterSale\ValueObject\AfterSaleAllowanceVo
    {
        $used = $this->afterSaleRepository->consumedAllowance((int) $item->id);
        return new \App\Domain\Trade\AfterSale\ValueObject\AfterSaleAllowanceVo(
            $item->order->items->toArray(), (int) $item->id,
            max(0, (int) $item->order->pay_amount - (int) $item->order->shipping_fee),
            $used['quantity'], $used['amount'],
        );
    }

    public function saveEntity(AfterSaleEntity $entity): bool
    {
        return $this->afterSaleRepository->updateFromEntity($entity);
    }

    public function getEntity(int $id): AfterSaleEntity
    {
        /** @var null|AfterSale $model */
        $model = $this->afterSaleRepository->findById($id);
        if ($model === null) {
            throw AfterSaleError::LOAD_FAILED->exception();
        }

        return AfterSaleMapper::fromModel($model);
    }

    private function resolveEligibleOrderItem(int $memberId, int $orderId, int $orderItemId): OrderItem
    {
        $orderItem = $this->orderRepository->findOrderItemForAfterSale($memberId, $orderId, $orderItemId);
        if ($orderItem === null) {
            throw AfterSaleError::ORDER_ITEM_INELIGIBLE->exception();
        }

        if ($this->afterSaleRepository->findActiveByOrderItemId($orderItemId) !== null) {
            throw AfterSaleError::ACTIVE_REQUEST_EXISTS->exception();
        }

        $status = (string) $orderItem->order->status;
        if (! \in_array($status, [OrderStatus::PAID->value, OrderStatus::PARTIAL_SHIPPED->value, OrderStatus::SHIPPED->value, OrderStatus::COMPLETED->value], true)) {
            throw AfterSaleError::ORDER_STATUS_UNSUPPORTED->exception();
        }

        if ($status === OrderStatus::COMPLETED->value && $this->isAfterSaleWindowExpired($orderItem)) {
            throw AfterSaleError::APPLICATION_PERIOD_EXPIRED->exception();
        }

        return $orderItem;
    }

    private function isAfterSaleWindowExpired(OrderItem $orderItem): bool
    {
        $afterSaleDays = $this->mallSettingService->order()->afterSaleDays();
        if ($afterSaleDays <= 0) {
            return false;
        }

        $completedAt = $orderItem->order->updated_at ?? null;
        if ($completedAt === null) {
            return false;
        }

        return $completedAt->copy()->addDays($afterSaleDays)->isPast();
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

    public function getLockedEntity(int $id): AfterSaleEntity
    {
        $model = $this->afterSaleRepository->findByIdForLock($id);
        if ($model === null) {
            throw AfterSaleError::NOT_FOUND->exception();
        }
        return AfterSaleMapper::fromModel($model);
    }
}
