<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Crontab;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\WecomScrm\Job\SyncWecomTagsJob;

#[Crontab(name: 'wecom-scrm-tags-sync', rule: '*/10 * * * *', callback: 'execute', memo: '同步企业微信标签组和标签', enable: true)]
final class WecomTagsSyncCrontab
{
    public function __construct(private readonly DriverFactory $driverFactory) {}

    public function execute(): void
    {
        $this->driverFactory->get('default')->push(new SyncWecomTagsJob());
    }
}
