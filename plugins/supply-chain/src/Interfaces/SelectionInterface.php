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

namespace Plugin\SupplyChain\Interfaces;

interface SelectionInterface
{
    public function selected(int $page = 1, int $pageSize = 50): array;

    public function add(array $externalProductIds): void;

    public function remove(array $externalProductIds): void;
}
