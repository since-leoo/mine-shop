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

namespace App\Interface\Admin\Dto\Order;

use App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput;

final class AfterSaleReviewDto implements AfterSaleReviewInput
{
    public int $id = 0;

    public ?int $approved_refund_amount = null;

    public string $reject_reason = '';

    public string $remark = '';

    public int $operator_id = 0;

    public string $operator_name = '';

    /**
     * 获取售后单 ID。
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 获取审核通过后的退款金额。
     */
    public function getApprovedRefundAmount(): ?int
    {
        return $this->approved_refund_amount;
    }

    /**
     * 获取审核拒绝原因。
     */
    public function getRejectReason(): string
    {
        return $this->reject_reason;
    }

    /**
     * 获取审核备注。
     */
    public function getRemark(): string
    {
        return $this->remark;
    }

    /**
     * 获取当前操作人 ID。
     */
    public function getOperatorId(): int
    {
        return $this->operator_id;
    }

    /**
     * 获取当前操作人名称。
     */
    public function getOperatorName(): string
    {
        return $this->operator_name;
    }
}