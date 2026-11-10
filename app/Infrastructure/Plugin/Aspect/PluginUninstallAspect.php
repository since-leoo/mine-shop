<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin\Aspect;

use App\Infrastructure\Plugin\PluginUninstallGuard;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;
use SinceLeoo\Plugin\PluginManager;

/** Vendor 管理器会吞掉 uninstall 钩子异常，因此必须在方法进入前执行。 */
#[Aspect(classes: [PluginManager::class . '::uninstall'])]
final class PluginUninstallAspect extends AbstractAspect
{
    public function __construct(private readonly PluginUninstallGuard $guard, private readonly PluginDiscovererInterface $plugins) {}

    public function process(ProceedingJoinPoint $point): mixed
    {
        $package = (string) ($point->arguments['keys']['packageName'] ?? '');
        $this->guard->assertCanUninstall($this->plugins->getPluginJsonConfig($package));
        return $point->process();
    }
}
