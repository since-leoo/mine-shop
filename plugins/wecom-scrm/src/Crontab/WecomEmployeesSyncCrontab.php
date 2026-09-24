<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Crontab;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\WecomScrm\Job\SyncWecomEmployeesJob;

#[Crontab(name: 'wecom-scrm-employees-sync', rule: '1-59/10 * * * *', callback: 'execute', memo: '同步企业微信员工通讯录', enable: true)]
final class WecomEmployeesSyncCrontab
{
    public function __construct(private readonly DriverFactory $driverFactory) {}

    public function execute(): void
    {
        $this->driverFactory->get('default')->push(new SyncWecomEmployeesJob());
    }
}
