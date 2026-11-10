<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Dto;

use App\Domain\Trade\AfterSale\Contract\AfterSaleActionInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput;

final readonly class CommerceAfterSaleActionData implements AfterSaleActionInput, AfterSaleReviewInput
{
    public function __construct(private int $id, private string $provider, private ?int $amount = null, private string $reason = '', private string $remark = '') {}
    public function getId(): int { return $this->id; }
    public function getOperatorId(): int { return 0; }
    public function getOperatorName(): string { return '履约服务:' . $this->provider; }
    public function getApprovedRefundAmount(): ?int { return $this->amount; }
    public function getRejectReason(): string { return $this->reason; }
    public function getRemark(): string { return $this->remark; }
}
