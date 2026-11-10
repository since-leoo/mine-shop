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

namespace Plugin\SupplyChain\Domain\Service;

use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Interfaces\Data\ProductQuery;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

final class DomainSupplyQueryService
{
    public function __construct(private readonly SupplyRepository $repository, private readonly SupplyChainManagerInterface $clients) {}

    public function connections(array $filters): array
    {
        return $this->repository->page('connections', $filters, []);
    }

    public function catalog(array $filters): array
    {
        $id = (int) ($filters['connection_id'] ?? 0);
        if ($id < 1) {
            throw new \InvalidArgumentException('请先选择供应链连接');
        }
        $page = $this->clients->forConnection($id)->catalog()->search(new ProductQuery((int) ($filters['page'] ?? 1), (int) ($filters['page_size'] ?? 20), (string) ($filters['q'] ?? '')));
        return ['list' => array_map(fn ($product) => $this->withImport($id, $product), $page['items']), 'total' => $page['total']];
    }

    public function catalogDetail(int $connectionId, string $externalId): array
    {
        $list = $this->clients->forConnection($connectionId)->catalog()->details([$externalId]);
        foreach ($list as $product) {
            if ($product['external_product_id'] === $externalId) {
                return $this->withImport($connectionId, $product);
            }
        }
        throw new \RuntimeException('上游未返回该商品详情');
    }

    public function products(array $filters): array
    {
        $query = $this->repository->query('product_links')->join('supply_products', 'supply_products.id', '=', 'supply_product_links.supply_product_id');
        foreach (['connection_id', 'status'] as $key) {
            if (! empty($filters[$key])) {
                $query->where('supply_product_links.' . $key, $filters[$key]);
            }
        }
        if (! empty($filters['q'])) {
            $query->where(static fn ($q) => $q->where('supply_products.name', 'like', '%' . $filters['q'] . '%')->orWhere('supply_products.external_product_id', 'like', '%' . $filters['q'] . '%'));
        }
        foreach (['created_from' => '>=', 'created_to' => '<='] as $key => $operator) {
            if (! empty($filters[$key])) {
                $query->where('supply_product_links.created_at', $operator, date('Y-m-d H:i:s', strtotime($filters[$key])));
            }
        }
        $total = (clone $query)->count();
        return ['list' => array_map($this->repository->decode(...), $query->select(['supply_product_links.*', 'supply_products.external_product_id', 'supply_products.name'])->orderByDesc('supply_product_links.id')->forPage(max(1, (int) ($filters['page'] ?? 1)), min(100, (int) ($filters['page_size'] ?? 20)))->get()->all()), 'total' => $total];
    }

    public function purchases(array $filters): array
    {
        return $this->repository->page('orders', $filters, ['connection_id', 'status', 'order_id']);
    }

    public function purchase(int $id): array
    {
        return $this->repository->require('orders', $id);
    }

    public function tasks(array $filters): array
    {
        return $this->repository->page('tasks', $filters, ['connection_id', 'status', 'type', 'trace_id']);
    }

    public function task(int $id): array
    {
        return $this->repository->require('tasks', $id) + [
            'attempts' => array_map($this->repository->decode(...), $this->repository->query('task_attempts')->where('task_id', $id)->orderBy('id')->get()->all()),
            'events' => array_map($this->repository->decode(...), $this->repository->query('task_events')->where('task_id', $id)->orderBy('id')->get()->all()),
            'logs' => array_map($this->repository->decode(...), $this->repository->query('api_logs')->where('task_id', $id)->orderByDesc('id')->limit(100)->get()->all()),
        ];
    }

    public function logs(array $filters): array
    {
        return $this->repository->page('api_logs', $filters, ['connection_id', 'status', 'task_id', 'trace_id']);
    }

    public function log(int $id): array
    {
        return $this->repository->require('api_logs', $id);
    }

    public function afterSales(array $filters): array
    {
        return $this->repository->page('after_sales', $filters, ['connection_id', 'status']);
    }

    public function afterSale(int $id): array
    {
        $row = $this->repository->require('after_sales', $id);
        $order = $this->repository->require('orders', (int) $row['supply_order_id']);
        return $row + ['external_orders' => $order['external_orders'] ?? []];
    }

    public function afterSaleTypes(int $connectionId, string $externalItemId): array
    {
        return $this->clients->forConnection($connectionId)->afterSales()->types($externalItemId);
    }

    private function withImport(int $connectionId, array $product): array
    {
        $remote = $this->repository->query('products')->where('connection_id', $connectionId)->where('external_product_id', $product['external_product_id'])->value('id');
        $product['imported'] = $remote !== null && $this->repository->query('product_links')->where('supply_product_id', $remote)->exists();
        $product['selected'] ??= null;
        unset($product['raw_snapshot']);
        return $product;
    }
}
