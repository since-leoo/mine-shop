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

namespace Plugin\SupplyChain\Application\Commad;

use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use Plugin\SupplyChain\Domain\Service\DomainSupplyPurchaseService;

final class AppSupplyTradeService
{
    public function __construct(private readonly TransactionManagerInterface $transactions, private readonly DomainSupplyPurchaseService $purchases, private readonly AppSupplyAfterSaleService $afterSales) {}

    public function paid(int $id): void
    {
        $this->transactions->run(fn () => $this->purchases->ensure($id));
    }

    public function notify(string $number, string $type): void
    {
        $this->transactions->run(fn () => $this->purchases->notify($number, $type));
    }

    public function afterSale(int $id, string $action): void
    {
        $this->afterSales->changed($id, $action);
    }
}
