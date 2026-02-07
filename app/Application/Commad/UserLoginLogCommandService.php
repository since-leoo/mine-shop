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

namespace App\Application\Commad;

use App\Domain\Logstash\Service\UserLoginLogService;

final class UserLoginLogCommandService
{
    public function __construct(private readonly UserLoginLogService $service) {}

    public function delete(mixed $ids): int
    {
        return $this->service->delete($ids);
    }
}
