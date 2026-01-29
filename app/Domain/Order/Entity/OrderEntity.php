<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Enum\PaymentStatus;
use App\Domain\Order\Enum\ShippingStatus;
use App\Domain\Order\ValueObject\OrderLogValue;
use App\Domain\Order\ValueObject\OrderPackageValue;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Stringable\Str;
use RuntimeException;

final class OrderEntity
{
    private int $id = 0;

    private string $orderNo = '';

    private int $memberId = 0;

    private string $status = OrderStatus::PENDING->value;

    private string $shippingStatus = ShippingStatus::PENDING->value;

    private string $payStatus = PaymentStatus::PENDING->value;

    private int $packageCount = 0;

    private string $originalStatus = OrderStatus::PENDING->value;

    private string $originalShippingStatus = ShippingStatus::PENDING->value;

    private string $originalPayStatus = PaymentStatus::PENDING->value;

    /**
     * @var null|OrderShipEntity
     */
    private ?OrderShipEntity $shipEntity = null;

    /**
     * @var OrderCancelEntity|null
     */
    private ?OrderCancelEntity $cancelEntity = null;

    /**
     * @var OrderPackageValue[]
     */
    private array $newPackages = [];

    public static function fromModel(Order $model): self
    {
        $entity = new self();
        $entity->setId((int) $model->id);
        $entity->setOrderNo((string) $model->order_no);
        $entity->setMemberId((int) $model->member_id);
        $entity->setStatus((string) $model->status);
        $entity->setShippingStatus((string) $model->shipping_status);
        $entity->setPayStatus((string) $model->pay_status);
        $entity->setPackageCount((int) ($model->package_count ?? 0));
        return $entity;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function getStatus(): string
    {
        return $this->status ?: OrderStatus::PENDING->value;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getShippingStatus(): string
    {
        return $this->shippingStatus ?: ShippingStatus::PENDING->value;
    }

    public function setShippingStatus(string $shippingStatus): void
    {
        $this->shippingStatus = $shippingStatus;
    }

    public function getPayStatus(): string
    {
        return $this->payStatus ?: PaymentStatus::PENDING->value;
    }

    public function setPayStatus(string $payStatus): void
    {
        $this->payStatus = $payStatus;
    }

    public function setShipEntity(OrderShipEntity $orderShipEntity): void
    {
        $this->shipEntity = $orderShipEntity;
    }

    public function getShipEntity(): ?OrderShipEntity
    {
        return $this->shipEntity;
    }

    public function getPackageCount(): int
    {
        return $this->packageCount;
    }

    public function setPackageCount(int $packageCount): void
    {
        $this->packageCount = max(0, $packageCount);
    }

    public function getOriginalStatus(): string
    {
        return $this->originalStatus;
    }

    public function getOriginalShippingStatus(): string
    {
        return $this->originalShippingStatus;
    }

    public function getOriginalPayStatus(): string
    {
        return $this->originalPayStatus;
    }

    public function ship(): self
    {
        if (! \in_array($this->getStatus(), [OrderStatus::PAID->value, OrderStatus::PARTIAL_SHIPPED->value], true)) {
            throw new RuntimeException('当前订单状态不可发货');
        }

        $packages = $this->getShipEntity()->getPackages();
        if (empty($packages)) {
            throw new RuntimeException('请至少提供一个包裹信息');
        }

        $this->setStatus(OrderStatus::SHIPPED->value);
        $this->setShippingStatus(ShippingStatus::SHIPPED->value);
        $this->setPackageCount($this->getPackageCount() + \count($packages));

        return $this;
    }

    public function cancel(): void
    {
        if (!\in_array($this->getStatus(), [OrderStatus::PENDING->value, OrderStatus::PAID->value], true)) {
            throw new RuntimeException('当前订单状态不可取消');
        }

        $this->setStatus(OrderStatus::CANCELLED->value);
        $this->setShippingStatus(ShippingStatus::PENDING->value);
        $this->setPayStatus(
            $this->getPayStatus() === PaymentStatus::PAID->value
                ? PaymentStatus::REFUNDED->value
                : PaymentStatus::CANCELLED->value
        );
    }
}
