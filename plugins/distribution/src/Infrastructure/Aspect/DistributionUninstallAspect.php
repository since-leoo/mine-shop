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

namespace Plugin\Distribution\Infrastructure\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Plugin\Distribution\Infrastructure\PluginUninstallGuard;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;
use SinceLeoo\Plugin\PluginManager;

/** Runs before the vendor manager, whose uninstall hook otherwise swallows exceptions. */
#[Aspect(classes: [PluginManager::class . '::uninstall'])]
final class DistributionUninstallAspect extends AbstractAspect
{
    public function __construct(private readonly PluginUninstallGuard $guard, private readonly PluginDiscovererInterface $plugins) {}

    public function process(ProceedingJoinPoint $point): mixed
    {
        $package = (string) ($point->arguments['keys']['packageName'] ?? '');
        if ($package === 'since/distribution') {
            $this->guard->assertCanUninstall($this->plugins->getPluginJsonConfig($package));
        }
        return $point->process();
    }
}
