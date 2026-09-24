<?php

namespace Plugin\WecomScrm\Library\Handler;

use GuzzleHttp\Exception\GuzzleException;
use Plugin\WecomScrm\Library\Abstract\WecomAbstract;
use Plugin\WecomScrm\Library\Interfaces\TagGroupInterface;
use Psr\SimpleCache\InvalidArgumentException;

final class TagGroupHandler extends WecomAbstract implements TagGroupInterface
{
    /**
     * @return array
     * @throws GuzzleException|InvalidArgumentException
     */
    public function getTagGroupList(): array
    {
        return $this->request('externalcontact/get_corp_tag_list', 'POST', []);
    }

    /**
     * @param string $name
     * @return array
     * @throws GuzzleException|InvalidArgumentException
     */
    public function createTagGroup(string $name): array
    {
        return $this->request('externalcontact/add_corp_tag', 'POST', ['group_name' => $name, 'tag' => []]);
    }

    /**
     * @param string $groupId
     * @param string $name
     * @return array
     * @throws GuzzleException|InvalidArgumentException
     */
    public function updateTagGroup(string $groupId, string $name): array
    {
        return $this->request('externalcontact/edit_corp_tag', 'POST', ['group_id' => $groupId, 'group_name' => $name]);
    }

    /**
     * @param string $groupId
     * @return array
     * @throws GuzzleException|InvalidArgumentException
     */
    public function deleteTagGroup(string $groupId): array
    {
        return $this->request('externalcontact/del_corp_tag', 'POST', ['group_id' => $groupId]);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function result(array $data): array
    {
        return $data['tag_group'] ?? $data;
    }
}
