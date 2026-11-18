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

namespace Plugin\Distribution\Infrastructure;

use App\Infrastructure\Error\DistributionError;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

/** Keeps financial history available when uninstall is requested through the active plugin. */
final class PluginUninstallGuard
{
    public function assertCanUninstall(array $definition): void
    {
        foreach ($definition['uninstall_activation_history_guards'] ?? [] as $table) {
            if (! $this->hasTable($table)) {
                continue;
            }
            // Outbox retries and in-flight submissions still require the financial handler.
            if (Db::table($table)->where('settings->enabled', true)->exists()) {
                throw DistributionError::FINANCIAL_PLUGIN_UNINSTALL_FORBIDDEN->exception();
            }
        }
        foreach ($definition['uninstall_retained_history_guards'] ?? [] as $table) {
            if ($this->hasTable($table) && Db::table($table)->exists()) {
                throw DistributionError::PLUGIN_HISTORY_UNINSTALL_FORBIDDEN->exception();
            }
        }
        foreach ($definition['uninstall_balance_guards'] ?? [] as $guard) {
            $table = $guard['table'] ?? '';
            if (! $this->hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($guard['positive_columns'] ?? [], $this->isIdentifier(...)));
            if ($columns === []) {
                continue;
            }
            $query = Db::table($table)->where(static function ($query) use ($columns): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, '>', 0);
                }
            });
            if ($query->exists()) {
                throw DistributionError::UNSETTLED_FUNDS_UNINSTALL_FORBIDDEN->exception();
            }
        }
        foreach ($definition['uninstall_submission_guards'] ?? [] as $guard) {
            $table = $guard['table'] ?? '';
            $key = $guard['payload_key'] ?? '';
            if (! $this->hasTable($table) || ! $this->isIdentifier($key)) {
                continue;
            }
            if (Db::table($table)->whereIn('status', ['pending', 'reserved'])
                ->whereRaw("JSON_LENGTH(JSON_EXTRACT(payload, '$.{$key}')) > 0")->exists()) {
                throw DistributionError::PENDING_ATTRIBUTION_UNINSTALL_FORBIDDEN->exception();
            }
        }
    }

    private function hasTable(mixed $table): bool
    {
        return $this->isIdentifier($table) && Schema::hasTable($table);
    }

    private function isIdentifier(mixed $value): bool
    {
        return \is_string($value) && preg_match('/^[a-z_]+$/', $value) === 1;
    }
}
