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

use Plugin\WecomScrm\Library\Abstract\WecomAbstract;

final class ContactHandler extends WecomAbstract
{
    public function listUsers(int $departmentId = 0): array
    {
        return $this->request('user/list', 'GET', ['department_id' => $departmentId]);
    }

    public function result(array $data): array
    {
        return $data;
    }
}
