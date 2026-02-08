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

namespace Plugin\Since\Seckill;

use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use Hyperf\Context\ApplicationContext;
use Plugin\Since\Seckill\Domain\Strategy\SeckillOrderStrategy;
use SinceLeoo\Plugin\Contract\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        // 迁移由插件管理器自动执行 Database/Migrations
    }

    public function uninstall(): void
    {
        // 回滚由 plugin.json 的 rollback_on_uninstall 控制
    }

    /**
     * 插件启动时注册秒杀订单策略到工厂.
     */
    public function boot(): void
    {
        $container = ApplicationContext::getContainer();
        $factory = $container->get(OrderTypeStrategyFactory::class);
        $factory->register($container->get(SeckillOrderStrategy::class));
    }
}
