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

use Hyperf\Context\ApplicationContext;
use Psr\Log\LoggerInterface;

if (!function_exists('system_message_logger')) {
    /**
     * 获取系统消息插件专用的 Logger 实例
     */
    function system_message_logger(): LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerInterface::class);
    }
}