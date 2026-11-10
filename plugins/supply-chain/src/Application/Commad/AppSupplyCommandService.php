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
use Plugin\SupplyChain\Domain\Contract\ConnectionInput;
use Plugin\SupplyChain\Domain\Contract\ImportInput;
use Plugin\SupplyChain\Domain\Service\DomainConnectionService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyProductService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyPurchaseService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyQueryService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyTaskService;
use Plugin\SupplyChain\Interfaces\Data\ProductQuery;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

final class AppSupplyCommandService
{
    public function __construct(private readonly TransactionManagerInterface $transactions, private readonly DomainConnectionService $connections, private readonly DomainSupplyProductService $products, private readonly DomainSupplyPurchaseService $purchases, private readonly DomainSupplyTaskService $tasks, private readonly DomainSupplyQueryService $queries, private readonly SupplyChainManagerInterface $clients, private readonly AppSupplyAfterSaleService $afterSales) {}

    public function saveConnection(?int $id, ConnectionInput $input, int $actor): array
    {
        $id = $this->transactions->run(fn () => $this->connections->save($id, $input, $actor));
        // Query service returns the domain record; the HTTP transformer owns credential omission.
        return $this->connections->find($id);
    }

    public function testConnection(int $id): array
    {
        $this->clients->forConnection($id)->catalog()->search(new ProductQuery(1, 1));
        return ['success' => true, 'message' => '认证及商品接口连接成功'];
    }

    public function importProducts(ImportInput $input, int $actor): array
    {
        return $this->transactions->run(fn () => $this->products->requestImports($input, $actor));
    }

    public function sync(int $id, int $actor): array
    {
        return ['task_id' => $this->transactions->run(fn () => $this->products->requestSync($id, $actor))];
    }

    public function queryPurchase(int $id, int $actor): array
    {
        return ['task_id' => $this->transactions->run(fn () => $this->purchases->queryTask($id, $actor))];
    }

    public function retry(int $id, int $actor, string $reason): array
    {
        $this->transactions->run(fn () => $this->tasks->retry($id, $actor, $reason));
        return ['task_id' => $id];
    }

    public function submitAfterSale(int $id, array $input, int $actor): array
    {
        return $this->afterSales->submit($id, $input, $actor);
    }

    public function queryAfterSale(int $id, int $actor): array
    {
        return $this->afterSales->query($id, $actor);
    }
}
