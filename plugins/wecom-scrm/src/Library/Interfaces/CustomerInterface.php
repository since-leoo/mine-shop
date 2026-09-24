<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Library\Interfaces;

interface CustomerInterface
{
    public function getExternalContact(string $userid, string $externalUserId): array;
    public function listExternalContacts(string $userid): array;
    public function updateRemark(string $userid, string $externalUserId, array $remark): array;
}
