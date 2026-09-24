<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\WecomScrm\Library\Handler;

use GuzzleHttp\Exception\GuzzleException;
use Plugin\WecomScrm\Library\Abstract\WecomAbstract;
use Plugin\WecomScrm\Library\Interfaces\TagInterface;
use Psr\SimpleCache\InvalidArgumentException;

final class TagHandler extends WecomAbstract implements TagInterface
{
    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    public function getTagList(): array
    {
        return $this->request('externalcontact/get_corp_tag_list', 'POST', []);
    }

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    public function getTagListByGroupId(string $groupId): array
    {
        return array_values(array_filter($this->getTagList(), static fn (array $group): bool => (string) ($group['group_id'] ?? '') === $groupId));
    }

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    public function createTag(string $name, string $groupId): array
    {
        return $this->request('externalcontact/add_corp_tag', 'POST', ['group_id' => $groupId, 'tag' => [['name' => $name]]]);
    }

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    public function updateTag(string $tagId, string $name, string $groupId = ''): array
    {
        return $this->request('externalcontact/edit_corp_tag', 'POST', ['id' => $tagId, 'name' => $name, 'group_id' => $groupId]);
    }

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    public function deleteTag(string $tagId, string $groupId = ''): array
    {
        return $this->request('externalcontact/del_corp_tag', 'POST', ['id' => $tagId, 'group_id' => $groupId]);
    }

    /**
     * @return mixed
     */
    public function result(array $data): array
    {
        return $data['tag_group'] ?? $data;
    }
}
