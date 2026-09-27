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

namespace Plugin\SystemMessage\Contract;

use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Facade\SystemMessage;

/**
 * 系统消息对外调用入口。
 * 主应用和其他插件只依赖这个公开 API，不直接依赖领域服务和模型。
 */
final class SystemMessageInterface
{
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return SystemMessage::$method(...$arguments);
    }
}
