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
                throw new \DomainException('该插件曾启用财务业务，不支持物理卸载；请在插件设置中关闭新增业务，保留历史结算与退款处理');
            }
        }
        foreach ($definition['uninstall_retained_history_guards'] ?? [] as $table) {
            if ($this->hasTable($table) && Db::table($table)->exists()) {
                throw new \DomainException('插件已有业务记录，不支持物理卸载；请在插件设置中关闭新增业务');
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
                throw new \DomainException('插件存在未结清资金，无法卸载；请先关闭新增业务并完成结算');
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
                throw new \DomainException('插件仍有待处理的归因订单，无法卸载');
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
