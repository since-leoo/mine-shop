<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

final class OrderLogValue
{
    private string $action = '';

    private string $description = '';

    private string $operatorType = 'admin';

    private int $operatorId = 0;

    private string $operatorName = '管理员';

    private ?string $oldStatus = null;

    private ?string $newStatus = null;

    /**
     * @var array<string, mixed>
     */
    private array $extraData = [];

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setOperatorType(string $operatorType): void
    {
        $this->operatorType = $operatorType;
    }

    public function getOperatorType(): string
    {
        return $this->operatorType;
    }

    public function setOperatorId(int $operatorId): void
    {
        $this->operatorId = $operatorId;
    }

    public function getOperatorId(): int
    {
        return $this->operatorId;
    }

    public function setOperatorName(string $operatorName): void
    {
        $this->operatorName = $operatorName;
    }

    public function getOperatorName(): string
    {
        return $this->operatorName;
    }

    public function setOldStatus(?string $oldStatus): void
    {
        $this->oldStatus = $oldStatus;
    }

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function setNewStatus(?string $newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    public function getNewStatus(): ?string
    {
        return $this->newStatus;
    }

    /**
     * @param array<string, mixed> $extraData
     */
    public function setExtraData(array $extraData): void
    {
        $this->extraData = $extraData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $orderId): array
    {
        return [
            'order_id' => $orderId,
            'action' => $this->getAction(),
            'description' => $this->getDescription(),
            'operator_type' => $this->getOperatorType(),
            'operator_id' => $this->getOperatorId(),
            'operator_name' => $this->getOperatorName(),
            'old_status' => $this->getOldStatus(),
            'new_status' => $this->getNewStatus(),
            'extra_data' => $this->getExtraData(),
        ];
    }
}
