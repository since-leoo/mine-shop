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

namespace App\Application\Permission\Assembler;

final class DepartmentAssembler
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function fromArray(array $payload): array
    {
        return $payload;
    }
}
