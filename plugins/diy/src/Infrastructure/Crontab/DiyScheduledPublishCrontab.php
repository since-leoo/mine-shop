<?php

declare(strict_types=1);

namespace Plugin\Diy\Infrastructure\Crontab;

use Hyperf\Crontab\Annotation\Crontab;
use Plugin\Diy\Application\Admin\Content\AppDiyScheduledPublishService;

#[Crontab(name: 'diy-scheduled-publish', rule: '* * * * *', callback: 'execute', memo: '执行到期的 DIY 页面定时发布', enable: true)]
final class DiyScheduledPublishCrontab
{
    public function __construct(private readonly AppDiyScheduledPublishService $publisher) {}

    public function execute(): void
    {
        $this->publisher->execute();
    }
}
