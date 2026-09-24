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

namespace App\Interface\Api\DTO\Auth;

use App\Domain\Member\Contract\VerificationCodeSendInput;

final class VerificationCodeSendDto implements VerificationCodeSendInput
{
    public string $phone = '';

    public string $scene = '';

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getScene(): string
    {
        return $this->scene;
    }
}
