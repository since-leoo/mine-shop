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

namespace App\Interface\Admin\Dto\Member;

use App\Domain\Member\Contract\MemberWalletInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 会员钱包 DTO.
 */
class MemberWalletDto implements MemberWalletInput
{
    #[Required]
    public int $member_id = 0;

    #[Required]
    public string $type = 'balance';

    #[Required]
    public float $value = 0.0;

    public string $source = 'manual';

    public string $remark = '';

    #[Required]
    public int $operator_id = 0;

    public function getMemberId(): int
    {
        return $this->member_id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
