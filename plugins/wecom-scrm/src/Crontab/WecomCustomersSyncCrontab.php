<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Crontab;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\WecomScrm\Job\SyncWecomCustomersJob;

#[Crontab(name: 'wecom-scrm-customers-sync', rule: '2-59/10 * * * *', callback: 'execute', memo: '同步企业微信客户关系', enable: true)]
final class WecomCustomersSyncCrontab
{
    public function __construct(private readonly DriverFactory $driverFactory) {}

    public function execute(): void
    {
        $this->driverFactory->get('default')->push(new SyncWecomCustomersJob());
    }
}
