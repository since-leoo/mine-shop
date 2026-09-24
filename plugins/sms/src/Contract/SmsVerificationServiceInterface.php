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

namespace Plugin\Sms\Contract;

interface SmsVerificationServiceInterface
{
    /**
     * @return array{phone: string, scene: string, code?: string}
     */
    public function sendCode(string $phone, string $scene): array;

    public function verifyCode(string $phone, string $scene, string $code): bool;
}
