<?php
namespace Plugin\WecomScrm\Library\Interfaces;
interface TagGroupInterface
{
    public function getTagGroupList(): array;

    public function createTagGroup(string $name): array;

    public function updateTagGroup(string $groupId, string $name): array;

    public function deleteTagGroup(string $groupId): array;
}
