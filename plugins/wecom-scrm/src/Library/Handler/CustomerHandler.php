<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Library\Handler;

use Plugin\WecomScrm\Library\Abstract\WecomAbstract;
use Plugin\WecomScrm\Library\Interfaces\CustomerInterface;

final class CustomerHandler extends WecomAbstract implements CustomerInterface
{
    public function getExternalContact(string $userid, string $externalUserId): array
    {
        return $this->request('externalcontact/get', 'GET', [
            'userid' => $userid,
            'external_userid' => $externalUserId,
        ]);
    }

    public function listExternalContacts(string $userid): array
    {
        return $this->request('externalcontact/list', 'GET', ['userid' => $userid]);
    }

    public function updateRemark(string $userid, string $externalUserId, array $remark): array
    {
        return $this->request('externalcontact/remark', 'POST', [
            'userid' => $userid,
            'external_userid' => $externalUserId,
            ...$remark,
        ]);
    }

    public function result(array $data): array
    {
        return $data['external_contact'] ?? $data;
    }
}
