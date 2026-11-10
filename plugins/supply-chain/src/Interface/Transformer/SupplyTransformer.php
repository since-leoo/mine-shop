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

namespace Plugin\SupplyChain\Interface\Transformer;

use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;

/** Explicit response allowlists keep credentials, execution leases and live order snapshots private. */
final class SupplyTransformer
{
    public function page(array $page, string $kind): array
    {
        if (! \in_array($kind, ['connection', 'catalog', 'product', 'purchase', 'task', 'log', 'afterSale'], true)) {
            throw new \InvalidArgumentException('供应链响应类型无效');
        }
        return [
            'list' => array_map(fn (array $row): array => $this->{$kind}($row), $page['list'] ?? []),
            'total' => (int) ($page['total'] ?? 0),
        ];
    }

    public function connection(array $row): array
    {
        $result = $this->only($row, ['id', 'name', 'app_key', 'driver', 'mode', 'enabled', 'credential_version', 'api_path', 'timeout_seconds', 'connect_timeout_seconds', 'created_at', 'updated_at']);
        $result['api_origin'] = $row['api_domain'] ?? $row['api_origin'] ?? '';
        $result['has_app_secret'] = ! empty($row['app_secret_ciphertext']) || ! empty($row['has_app_secret']);
        $carriers = [];
        foreach ($row['settings']['return_carriers'] ?? [] as $localName => $carrier) {
            if (\is_array($carrier) && isset($carrier['name'], $carrier['code']) && \is_string($carrier['name']) && \is_string($carrier['code'])) {
                $carriers[(string) $localName] = $this->only($carrier, ['name', 'code']);
            }
        }
        $result['settings'] = ['return_carriers' => (object) $carriers];
        return $result;
    }

    public function connectionTest(array $row): array
    {
        return $this->safe($this->only($row, ['success', 'message', 'request_id', 'duration_ms']));
    }

    public function receipt(array $row): array
    {
        return $this->only($row, ['task_id', 'task_ids', 'dispatch_version', 'status']);
    }

    public function catalog(array $row): array
    {
        $result = $this->only($row, ['external_product_id', 'name', 'description', 'stock', 'cost_price', 'sale_price', 'available']);
        $result['external_product_id'] = (string) ($row['external_product_id'] ?? '');
        $result['image'] = $row['main_image'] ?? $row['image'] ?? '';
        $result['imported'] = (bool) ($row['imported'] ?? false);
        // Unknown upstream selection state must not be presented as "not selected".
        $result['selected'] = isset($row['selected']) ? (bool) $row['selected'] : null;
        $result['skus'] = array_map(fn (array $sku): array => $this->only($sku, ['external_sku_id', 'spec_values', 'cost_price', 'sale_price', 'stock']) + ['name' => $sku['title'] ?? $sku['name'] ?? ''], $row['skus'] ?? []);
        if (! isset($result['cost_price']) && $result['skus'] !== []) {
            $prices = array_column($result['skus'], 'cost_price');
            if ($prices !== []) {
                $result['cost_price'] = min($prices);
            }
        }
        if (! isset($result['stock']) && $result['skus'] !== []) {
            $result['stock'] = array_sum(array_column($result['skus'], 'stock'));
        }
        return $result;
    }

    public function product(array $row): array
    {
        return $this->safe($this->only($row, ['id', 'connection_id', 'product_id', 'external_product_id', 'name', 'status', 'binding_version', 'last_synced_at', 'last_error', 'created_at', 'updated_at']));
    }

    public function purchase(array $row): array
    {
        $result = $this->only($row, ['id', 'connection_id', 'order_id', 'order_no', 'merchant_order_no', 'status', 'last_error', 'task_id', 'created_at', 'updated_at']);
        $result['external_orders'] = $this->externalOrders($row['external_orders'] ?? []);
        $result['external_order_no'] = implode(', ', array_column($result['external_orders'], 'external_order_no'));
        return $this->safe($result);
    }

    public function afterSale(array $row): array
    {
        $result = $this->only($row, ['id', 'connection_id', 'after_sale_id', 'supply_order_id', 'external_after_sale_id', 'status', 'last_error', 'created_at', 'updated_at']);
        if (isset($row['snapshot'])) {
            $result['snapshot'] = $this->safe($row['snapshot']);
            $candidate = $row['snapshot']['candidate_external_after_sale_id'] ?? null;
            if (\is_string($candidate) && $candidate !== '') {
                // A lookup by purchase line is only an observation, never an adopted supplier refund.
                $result['candidate_external_after_sale_id'] = $candidate;
            }
        }
        if (isset($row['external_orders'])) {
            $result['external_orders'] = $this->externalOrders($row['external_orders']);
        }
        return $this->safe($result);
    }

    public function task(array $row, bool $parameters = false): array
    {
        $result = $this->only($row, ['id', 'connection_id', 'type', 'status', 'business_key', 'attempt_count', 'max_attempts', 'dispatch_version', 'trace_id', 'next_retry_at', 'last_error', 'operator_id', 'created_at', 'updated_at']);
        $result['retryable'] = \in_array($row['status'] ?? '', ['failed', 'retry_wait'], true);
        $result['next_action'] = ($row['status'] ?? '') === 'unknown' ? 'query' : ($result['retryable'] ? 'retry' : null);
        if (isset($row['attempts'])) {
            $result['attempts'] = array_map(fn (array $attempt): array => $this->attempt($attempt, $parameters), $row['attempts']);
        }
        if (isset($row['logs'])) {
            // Full payloads always use the separately permissioned request detail endpoint.
            $result['logs'] = array_map(fn (array $log): array => $this->log($log), $row['logs']);
        }
        if (isset($row['events'])) {
            $result['events'] = array_map(fn (array $event): array => $this->only($event, $parameters
                ? ['id', 'task_id', 'action', 'detail', 'operator_id', 'created_at']
                : ['id', 'task_id', 'action', 'operator_id', 'created_at']), $row['events']);
        }
        if ($parameters) {
            $result['payload'] = $row['payload'] ?? [];
            $result['result'] = $row['result'] ?? null;
        }
        return $this->safe($result);
    }

    public function attempt(array $row, bool $parameters = false): array
    {
        $result = $this->only($row, ['id', 'task_id', 'attempt_no', 'status', 'started_at', 'finished_at', 'duration_ms', 'failure_stage', 'error_category', 'error_message', 'next_action']);
        if ($parameters) {
            $result['request'] = $row['parameters'] ?? $row['request'] ?? [];
            $result['response'] = $row['result'] ?? $row['response'] ?? null;
        }
        return $this->safe($result);
    }

    public function log(array $row, bool $parameters = false): array
    {
        $result = $this->only($row, ['id', 'connection_id', 'task_id', 'attempt_id', 'request_id', 'trace_id', 'operation', 'method', 'path', 'status', 'http_status', 'provider_code', 'duration_ms', 'error_message', 'created_at', 'finished_at']);
        if ($parameters) {
            $result['request'] = $row['request_data'] ?? $row['request'] ?? [];
            $result['response'] = $row['response_data'] ?? $row['response'] ?? null;
        }
        return $this->safe($result);
    }

    public function safe(mixed $value): mixed
    {
        return SafeLogVo::sanitize($value);
    }

    private function only(array $row, array $fields): array
    {
        return array_intersect_key($row, array_flip($fields));
    }

    private function externalOrders(array $orders): array
    {
        return array_map(function (array $order): array {
            return [
                'external_order_no' => (string) ($order['external_order_no'] ?? $order['order_sn'] ?? ''),
                'status' => $order['provider_status'] ?? $order['status'] ?? null,
                'items' => array_map(fn (array $item): array => $this->only($item, ['external_order_item_id', 'external_sku_id', 'quantity', 'amount_cents', 'cost_price', 'order_item_id']), $order['items'] ?? []),
            ];
        }, array_values($orders));
    }
}
