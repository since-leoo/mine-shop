<?php
namespace Plugin\WecomScrm\Library\Interfaces;
interface TagInterface
{
    public function getTagList(): array;

    public function getTagListByGroupId(string $groupId): array;

    public function createTag(string $name, string $groupId): array;

    public function updateTag(string $tagId, string $name, string $groupId = ''): array;

    public function deleteTag(string $tagId, string $groupId = ''): array;
}
