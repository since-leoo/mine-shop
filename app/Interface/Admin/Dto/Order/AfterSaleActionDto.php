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

use App\Domain\Trade\AfterSale\Contract\AfterSaleActionInput;

final class AfterSaleActionDto implements AfterSaleActionInput
{
    public int $id = 0;

    public int $operator_id = 0;

    public string $operator_name = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }

    public function getOperatorName(): string
    {
        return $this->operator_name;
    }
}
