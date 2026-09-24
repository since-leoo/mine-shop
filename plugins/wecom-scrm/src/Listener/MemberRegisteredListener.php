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

use App\Domain\Member\Event\MemberRegistered;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\WecomScrm\Service\WecomApiService;

#[Listener]
final class MemberRegisteredListener implements ListenerInterface
{
    public function __construct(private readonly WecomApiService $api) {}

    public function listen(): array
    {
        return [MemberRegistered::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof MemberRegistered && $this->api->enabled()) {
            // 仅在存在用户绑定时由绑定服务补充 external_userid，事件本身不耦合主应用模型。
        }
    }
}
