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

namespace Plugin\WecomScrm\Service;

use Plugin\WecomScrm\Library\Handler\CustomerHandler;

final class CustomerApiService
{
    public function __construct(private readonly CustomerHandler $handler) {}

    public function list(string $userid): array
    {
        return $this->handler->listExternalContacts($userid);
    }

    public function detail(string $userid, string $externalUserId): array
    {
        return $this->handler->getExternalContact($userid, $externalUserId);
    }

    public function remark(string $userid, string $externalUserId, array $data): array
    {
        return $this->handler->updateRemark($userid, $externalUserId, $data);
    }
}
