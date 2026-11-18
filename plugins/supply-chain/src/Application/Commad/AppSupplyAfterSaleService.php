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
use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Domain\Service\DomainSupplyAfterSaleService;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

/** Network operations deliberately happen outside local database transactions. */
final class AppSupplyAfterSaleService
{
    public function __construct(
        private readonly DomainSupplyAfterSaleService $domain,
        private readonly SupplyChainManagerInterface $clients,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function changed(int $afterSaleId, string $action): void
    {
        $this->transactions->run(fn () => $this->domain->changed($afterSaleId, $action));
    }

    public function submit(int $id, array $input, int $actor): array
    {
        $preview = $this->domain->previewSubmission($id, $input);
        $types = $this->clients->forConnection($preview['connection_id'])->afterSales()->types($preview['external_order_item_id']);
        return $this->transactions->run(fn () => $this->domain->submit($id, $input, $types, $actor));
    }

    public function query(int $id, int $actor): array
    {
        return $this->transactions->run(fn () => $this->domain->query($id, $actor));
    }

    public function execute(array $task): array
    {
        try {
            $context = $this->transactions->run(fn () => $this->domain->start($task));
            $afterSales = $this->clients->forConnection($context['connection_id'])->afterSales();
            $result = match ($task['type']) {
                'after_sale_create' => $afterSales->create($context['command']),
                'after_sale_query' => ! empty($context['external_after_sale_id'])
                    ? $afterSales->find($context['external_after_sale_id']) : $afterSales->findByOrderItem($context['external_order_item_id']),
                'after_sale_cancel' => $afterSales->close($context['external_after_sale_id']),
                'after_sale_return' => $afterSales->shipReturn(
                    $context['external_after_sale_id'],
                    $context['return_shipment']['company_name'],
                    $context['return_shipment']['company_code'],
                    $context['return_shipment']['tracking_no']
                ),
                default => throw SupplyChainError::UNKNOWN_AFTER_SALE_TASK->exception(),
            };
            return $this->transactions->run(fn () => $this->domain->finish($task, $result));
        } catch (\Throwable $error) {
            $this->transactions->run(fn () => $this->domain->failure($task, $error));
            throw $error;
        }
    }
}
