<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy;

use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use Hyperf\Context\ApplicationContext;
use Plugin\Since\GroupBuy\Domain\Strategy\GroupBuyOrderStrategy;
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
     * 插件启动时注册拼团订单策略到工厂.
     */
    public function boot(): void
    {
        $container = ApplicationContext::getContainer();
        $factory = $container->get(OrderTypeStrategyFactory::class);
        $factory->register($container->get(GroupBuyOrderStrategy::class));
    }
}
