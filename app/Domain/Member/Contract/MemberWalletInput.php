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

namespace App\Domain\Member\Contract;

/**
 * 会员钱包输入契约接口.
 */
interface MemberWalletInput
{
    public function getMemberId(): int;

    public function getType(): string;

    public function getValue(): float;

    public function getSource(): string;

    public function getRemark(): string;

    public function getOperatorId(): int;
}
