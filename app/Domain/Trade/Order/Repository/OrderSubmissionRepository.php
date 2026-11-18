<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Repository;

use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Infrastructure\Error\OrderError;
use Hyperf\DbConnection\Db;

class OrderSubmissionRepository implements OrderSubmissionStoreInterface
{
    public function create(string $tradeNo, int $memberId, array $payload): void
    {
        Db::table('order_submissions')->insertOrIgnore([
            'trade_no' => $tradeNo, 'member_id' => $memberId, 'status' => 'pending',
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $existing = $this->find($tradeNo);
        if ($existing === null || (int) $existing['member_id'] !== $memberId || $existing['payload'] != $payload) {
            throw OrderError::SUBMISSION_CONFLICT->exception();
        }
    }

    public function find(string $tradeNo): ?array
    {
        return $this->decode(Db::table('order_submissions')->where('trade_no', $tradeNo)->first());
    }

    public function lock(string $tradeNo): ?array
    {
        return $this->decode(Db::table('order_submissions')->where('trade_no', $tradeNo)->lockForUpdate()->first());
    }

    public function updateStatus(string $tradeNo, string $status, string $error = ''): void
    {
        Db::table('order_submissions')->where('trade_no', $tradeNo)->update([
            'status' => $status, 'last_error' => mb_substr($error, 0, 1000), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function pending(int $limit = 100): array
    {
        return Db::table('order_submissions')->whereIn('status', ['pending', 'reserved'])
            ->where('updated_at', '<=', date('Y-m-d H:i:s', time() - 30))->orderBy('updated_at')->orderBy('id')->limit($limit)
            ->pluck('trade_no')->all();
    }

    private function decode(?object $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $data = (array) $row;
        $data['payload'] = json_decode($data['payload'], true, 512, JSON_THROW_ON_ERROR);
        return $data;
    }
}
