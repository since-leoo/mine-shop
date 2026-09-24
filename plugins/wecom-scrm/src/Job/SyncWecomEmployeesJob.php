<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\WecomScrm\Library\Handler\ContactHandler;
use Plugin\WecomScrm\Model\WecomEmployee;

final class SyncWecomEmployeesJob extends Job
{
    public function handle(): void
    {
        $result = ApplicationContext::getContainer()->get(ContactHandler::class)->listUsers(0);
        foreach ($result['userlist'] ?? [] as $user) {
            $userid = (string) ($user['userid'] ?? '');
            if ($userid === '') {
                continue;
            }
            WecomEmployee::query()->updateOrCreate(
                ['wecom_userid' => $userid],
                ['name' => (string) ($user['name'] ?? ''), 'mobile' => $user['mobile'] ?? null, 'email' => $user['email'] ?? null, 'department_id' => $user['department'][0] ?? null, 'enable' => (bool) ($user['enable'] ?? true)],
            );
        }
    }
}
