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

namespace App\Domain\Trade\Shared\Repository;

use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use Hyperf\DbConnection\Db;

class TradeOutboxRepository implements TradeOutboxInterface
{
    public function append(string $key, string $type, array $payload): void
    {
        // 唯一业务键消除同一事务重试/回调重放产生的重复事件，调用方负责与业务写入同事务。
        Db::table('trade_outbox')->insertOrIgnore([
            'business_key' => $key, 'event_type' => $type,
            'payload' => json_encode($payload, \JSON_THROW_ON_ERROR),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function pending(int $limit = 100): array
    {
        return Db::table('trade_outbox')->whereNull('processed_at')->orderBy('updated_at')->orderBy('id')->limit($limit)->get()->all();
    }

    public function lock(int $id): ?object
    {
        return Db::table('trade_outbox')->where('id', $id)->lockForUpdate()->first();
    }

    public function complete(int $id): void
    {
        Db::table('trade_outbox')->where('id', $id)->update(['processed_at' => date('Y-m-d H:i:s'), 'last_error' => null]);
    }

    public function recordFailure(int $id, string $error): void
    {
        Db::table('trade_outbox')->where('id', $id)->increment('attempts', 1, ['last_error' => mb_substr($error, 0, 1000), 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
