<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\Enum\ShippingStatus;
use App\Domain\Order\ValueObject\OrderPackageValue;
use Carbon\Carbon;

final class OrderShipEntity
{
    private int $orderId = 0;

    /**
     * @var OrderPackageValue[]
     */
    private array $packages = [];

    private int $operatorId = 0;

    private string $operatorName = '';

    private ?Carbon $shippedAt = null;

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOperatorId(int $param)
    {
        $this->operatorId = $param;
    }

    public function setOperatorName(string $param)
    {
        $this->operatorName = $param;
    }

    public function getOperatorId(): int
    {
        return $this->operatorId;
    }

    public function getOperatorName(): string
    {
        return $this->operatorName;
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     */
    public function setPackages(array $packages): void
    {
        $values = [];

        foreach ($packages as $package) {
            $company = (string) ($package['shipping_company'] ?? '');
            $number = (string) ($package['shipping_no'] ?? '');
            if ($company === '' || $number === '') {
                continue;
            }

            $value = new OrderPackageValue();
            $value->setShippingCompany($company);
            $value->setShippingNo($number);
            $value->setRemark((string) ($package['remark'] ?? ''));
            $value->setQuantity((int) ($package['quantity'] ?? 0));
            $value->setWeight((float) ($package['weight'] ?? 0));
            $value->setStatus(ShippingStatus::SHIPPED->value);
            $value->setShippedAt($this->getShippedAt());
            $values[] = $value;
        }

        $this->packages = $values;
    }

    /**
     * @return OrderPackageValue[]
     */
    public function getPackages(): array
    {
        return array_map(function (OrderPackageValue $package) {
            return $package->toArray($this->orderId);
        }, $this->packages);
    }

    public function setShippedAt(?Carbon $shippedAt): void
    {
        $this->shippedAt = $shippedAt;
    }

    public function getShippedAt(): Carbon
    {
        return $this->shippedAt ?? Carbon::now();
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'packages' => array_map(function (OrderPackageValue $package) {
                return $package->toArray($this->orderId);
            }, $this->packages),
            'shipped_at' => $this->shippedAt?->toDateTimeString(),
        ];
    }
}
