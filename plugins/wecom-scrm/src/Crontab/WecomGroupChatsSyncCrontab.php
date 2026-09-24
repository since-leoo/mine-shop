<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Crontab;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\WecomScrm\Job\SyncWecomGroupChatsJob;

#[Crontab(name: 'wecom-scrm-group-chats-sync', rule: '3-59/10 * * * *', callback: 'execute', memo: '同步企业微信群聊', enable: true)]
final class WecomGroupChatsSyncCrontab
{
    public function __construct(private readonly DriverFactory $driverFactory) {}

    public function execute(): void
    {
        $this->driverFactory->get('default')->push(new SyncWecomGroupChatsJob());
    }
}
