<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\WecomScrm\Library\Handler\TagGroupHandler;
use Plugin\WecomScrm\Model\WecomTag;
use Plugin\WecomScrm\Model\WecomTagGroup;

final class SyncWecomTagsJob extends Job
{
    public function handle(): void
    {
        $result = ApplicationContext::getContainer()->get(TagGroupHandler::class)->getTagGroupList();
        foreach ($result['tag_group'] ?? [] as $group) {
            $groupId = (string) ($group['group_id'] ?? '');
            if ($groupId === '') {
                continue;
            }
            WecomTagGroup::query()->updateOrCreate(
                ['group_id' => $groupId],
                ['name' => (string) ($group['group_name'] ?? $group['name'] ?? ''), 'is_editable' => true],
            );
            foreach ($group['tag'] ?? [] as $tag) {
                $tagId = (string) ($tag['id'] ?? '');
                if ($tagId === '') {
                    continue;
                }
                WecomTag::query()->updateOrCreate(
                    ['tag_id' => $tagId],
                    ['group_id' => $groupId, 'name' => (string) ($tag['name'] ?? ''), 'color' => $tag['color'] ?? null],
                );
            }
        }
    }
}
