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

class InstallScript
{
    public function __invoke(): void
    {
        echo '开始安装...' . \PHP_EOL;
        // 获取配置模板
        $conf = file_get_contents(__DIR__ . '/../publish/wechat.php');
        // 写入配置
        file_put_contents(BASE_PATH . '/config/autoload/wechat.php', $conf);
        echo '安装处理完毕...' . \PHP_EOL;
    }
}
