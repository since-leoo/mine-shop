<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Infrastructure\Error\PluginError;

use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

/** 宿主统一执行声明式卸载保护；插件停用后也必须保留历史和在途业务。 */
final class PluginUninstallGuard
{
    public function assertCanUninstall(array $definition): void
    {
        foreach ($definition['uninstall_activation_history_guards'] ?? [] as $table) {
            if ($this->hasTable($table) && Db::table($table)->where('settings->enabled', true)->exists()) {
                throw PluginError::FINANCIAL_UNINSTALL_FORBIDDEN->exception();
            }
        }
        foreach ($definition['uninstall_retained_history_guards'] ?? [] as $table) {
            if ($this->hasTable($table) && Db::table($table)->exists()) {
                throw PluginError::DATA_UNINSTALL_FORBIDDEN->exception();
            }
        }
        foreach ($definition['uninstall_balance_guards'] ?? [] as $guard) {
            if (! $this->hasTable($guard['table'] ?? '')) { continue; }
            $columns = $guard['positive_columns'] ?? [];
            foreach ($columns as $column) { $this->assertIdentifier($column); }
            if ($columns !== [] && Db::table($guard['table'])->where(static function ($query) use ($columns): void {
                foreach ($columns as $column) { $query->orWhere($column, '>', 0); }
            })->exists()) {
                throw PluginError::UNSETTLED_FUNDS->exception();
            }
        }
        foreach ($definition['uninstall_submission_guards'] ?? [] as $guard) {
            if (! $this->hasTable($guard['table'] ?? '')) { continue; }
            $key = $guard['payload_key'] ?? '';
            $this->assertIdentifier($key);
            $rows = Db::table($guard['table'])->whereIn('status', ['pending', 'reserved'])->select(['payload'])->cursor();
            foreach ($rows as $row) {
                $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
                $blocked = ! empty($payload[$key]);
                if ($key === 'fulfillment') {
                    // 兼容首次版本已保存的订单：冻结信息在 items 中，尚无顶层摘要。
                    foreach ($payload['items'] ?? [] as $item) {
                        if (($item['fulfillment_provider'] ?? 'merchant') !== 'merchant' && ! empty($item['fulfillment_snapshot'])) { $blocked = true; break; }
                    }
                }
                if ($blocked) { throw PluginError::PENDING_ORDERS->exception(); }
            }
        }
    }

    private function hasTable(mixed $table): bool
    {
        $this->assertIdentifier($table);
        return Schema::hasTable($table);
    }

    private function assertIdentifier(mixed $value): void
    {
        if (! is_string($value) || preg_match('/^[a-z_][a-z0-9_]*$/D', $value) !== 1) {
            throw new \LogicException(\App\Infrastructure\Error\PluginError::UNINSTALL_DECLARATION_INVALID->message(), \App\Infrastructure\Error\PluginError::UNINSTALL_DECLARATION_INVALID->value);
        }
    }
}
