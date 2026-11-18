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

namespace Plugin\SupplyChain\Domain\Repository;

use App\Infrastructure\Error\SupplyChainError;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;

/** Persistence is limited to plugin-owned tables. No joins against commerce tables. */
class SupplyRepository
{
    private const TABLES = ['connections', 'products', 'skus', 'product_links', 'sku_links', 'orders', 'order_items', 'shipments', 'after_sales', 'messages', 'tasks', 'task_attempts', 'task_events', 'api_logs'];

    private const JSON = ['settings', 'snapshot', 'sync_policy', 'external_orders', 'items', 'payload', 'result', 'parameters', 'detail', 'request_data', 'response_data'];

    public function query(string $table): Builder
    {
        if (! \in_array($table, self::TABLES, true)) {
            throw SupplyChainError::TABLE_NOT_ALLOWED->invalid();
        }
        return Db::table('supply_' . $table);
    }

    public function find(string $table, int $id, bool $lock = false): ?array
    {
        $query = $this->query($table)->where('id', $id);
        return $this->decode($lock ? $query->lockForUpdate()->first() : $query->first());
    }

    public function require(string $table, int $id, bool $lock = false): array
    {
        return $this->find($table, $id, $lock) ?? throw SupplyChainError::RECORD_NOT_FOUND->internal();
    }

    public function insert(string $table, array $data): int
    {
        return (int) $this->query($table)->insertGetId($this->encode($data));
    }

    public function update(string $table, int $id, array $data): void
    {
        if ($data !== []) {
            $this->query($table)->where('id', $id)->update($this->encode($data));
        }
    }

    public function page(string $table, array $filters, array $filterColumns): array
    {
        $query = $this->query($table);
        foreach ($filterColumns as $column) {
            if (isset($filters[$column]) && $filters[$column] !== '') {
                $query->where($column, $filters[$column]);
            }
        }
        foreach (['created_from' => '>=', 'created_to' => '<='] as $key => $operator) {
            if (! empty($filters[$key])) {
                $query->where('created_at', $operator, date('Y-m-d H:i:s', strtotime($filters[$key])));
            }
        }
        $searchColumns = match ($table) {
            'connections' => ['name', 'api_domain'], 'products' => ['name', 'external_product_id'],
            'orders' => ['order_no', 'merchant_order_no', 'last_error'], 'tasks' => ['type', 'business_key', 'last_error'],
            'api_logs' => ['path', 'operation', 'error_message'], 'after_sales' => ['external_after_sale_id', 'last_error'], default => [],
        };
        if (! empty($filters['q']) && $searchColumns !== []) {
            $query->where(static function ($query) use ($searchColumns, $filters): void {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', '%' . $filters['q'] . '%');
                }
            });
        }
        $total = (clone $query)->count();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $size = min(100, max(1, (int) ($filters['page_size'] ?? 20)));
        return ['list' => array_map($this->decode(...), $query->orderByDesc('id')->forPage($page, $size)->get()->all()), 'total' => $total];
    }

    public function mappingBySku(int $skuId): ?array
    {
        $link = $this->decode($this->query('sku_links')->where('sku_id', $skuId)->first());
        if ($link === null) {
            return null;
        }
        $sku = $this->require('skus', (int) $link['supply_sku_id']);
        $productLink = $this->require('product_links', (int) $link['product_link_id']);
        $product = $this->require('products', (int) $sku['supply_product_id']);
        return ['link' => $link, 'sku' => $sku, 'product_link' => $productLink, 'product' => $product];
    }

    public function decode(array|object|null $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $data = (array) $row;
        foreach (self::JSON as $key) {
            if (isset($data[$key]) && \is_string($data[$key])) {
                $data[$key] = json_decode($data[$key], true, 512, \JSON_THROW_ON_ERROR);
            }
        }
        return $data;
    }

    public function encode(array $data): array
    {
        foreach (self::JSON as $key) {
            if (isset($data[$key]) && \is_array($data[$key])) {
                $data[$key] = json_encode($data[$key], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
            }
        }
        return $data;
    }
}
