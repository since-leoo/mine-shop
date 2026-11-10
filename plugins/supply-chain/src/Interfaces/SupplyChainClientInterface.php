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

interface SupplyChainClientInterface
{
    public function catalog(): CatalogInterface;

    public function selection(): SelectionInterface;

    public function quotes(): QuoteInterface;

    public function orders(): OrderInterface;

    public function afterSales(): AfterSaleInterface;

    public function messages(): MessageInterface;
}
