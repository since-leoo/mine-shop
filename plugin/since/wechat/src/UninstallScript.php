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

namespace Plugin\Wechat;

class UninstallScript
{
    public function __invoke(): void
    {
        echo '开始卸载...' . \PHP_EOL;
        // 获取配置模板
        $file = __DIR__ . '/../publish/wechat.php';
        // 删除配置文件
        if (file_exists($file)) {
            @unlink($file);
        }
        echo '卸载处理完毕...' . \PHP_EOL;
    }
}
