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

namespace Plugin\Distribution\Interface\Dto;

use Plugin\Distribution\Domain\Contract\WithdrawalReviewInput;

final class WithdrawalReviewDto implements WithdrawalReviewInput
{
    public function __construct(private readonly array $data) {}

    public function toArray(): array
    {
        $data = $this->data;
        foreach (['payment_no', 'remark'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        return $data;
    }
}
