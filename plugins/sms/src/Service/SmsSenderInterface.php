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

namespace Plugin\Sms\Service;

interface SmsSenderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $config
     */
    public function send(string $phone, array $payload, array $config): void;
}
