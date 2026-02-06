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

namespace App\Application\Query;

use App\Domain\SystemSetting\Service\SystemSettingService;

final class SystemSettingQueryService
{
    public function __construct(
        private readonly SystemSettingService $service
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->service->get($key, $default);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function group(string $group): array
    {
        return $this->service->groupDetails($group);
    }

    /**
     * @return array<int, array{key:string,label:string,description:?string}>
     */
    public function groups(): array
    {
        return $this->service->groups();
    }
}
