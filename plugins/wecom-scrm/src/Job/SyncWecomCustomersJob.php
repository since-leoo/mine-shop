<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\WecomScrm\Library\Handler\CustomerHandler;
use Plugin\WecomScrm\Model\WecomCustomer;
use Plugin\WecomScrm\Model\WecomEmployee;

final class SyncWecomCustomersJob extends Job
{
    public function handle(): void
    {
        $handler = ApplicationContext::getContainer()->get(CustomerHandler::class);
        foreach (WecomEmployee::query()->pluck('wecom_userid') as $userid) {
            $userid = (string) $userid;
            foreach ($handler->listExternalContacts($userid)['external_userid'] ?? [] as $externalId) {
                $externalId = (string) $externalId;
                if ($externalId === '') {
                    continue;
                }
                WecomCustomer::query()->updateOrCreate(
                    ['external_userid' => $externalId],
                    ['follow_userid' => $userid],
                );
            }
        }
    }
}
