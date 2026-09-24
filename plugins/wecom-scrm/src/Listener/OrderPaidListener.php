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

namespace Plugin\WecomScrm\Listener;

use App\Domain\Member\Event\OrderPaidForMember;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\WecomScrm\Service\WecomApiService;

#[Listener]
final class OrderPaidListener implements ListenerInterface
{
    public function __construct(private readonly WecomApiService $api) {}

    public function listen(): array
    {
        return [OrderPaidForMember::class];
    }

    public function process(object $event): void
    {
        if (! $event instanceof OrderPaidForMember || ! $this->api->enabled()) {
            return;
        }
        // 标签规则从插件配置读取，具体客户绑定由 wecom-user-binding 模块负责。
    }
}
