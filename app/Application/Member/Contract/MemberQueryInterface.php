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

namespace App\Application\Member\Contract;

interface MemberQueryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array;

    /**
     * @return array<string, int>
     */
    public function stats(array $filters): array;

    /**
     * @return array<string, mixed>
     */
    public function overview(array $filters): array;

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array;
}
