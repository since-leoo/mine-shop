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

namespace App\Domain\Trade\Order\Service;

use App\Infrastructure\Abstract\ICache;

/**
 * 异步下单状态缓存服务.
 *
 * 用 Redis Hash 存储每笔待处理订单的状态和快照数据。
 * Key: order:pending:{tradeNo}
 * Fields: status (processing/created/failed), error, entity_snapshot, pay_methods
 * TTL: 10 分钟（超时自动清理）
 */
final class OrderPendingCacheService
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_CREATED = 'created';

    public const STATUS_FAILED = 'failed';

    private const PREFIX = 'order:pending';

    private const TTL = 600; // 10 分钟

    public function __construct(private readonly ICache $cache) {}

    /**
     * 标记订单为处理中，存储快照数据.
     */
    public function markProcessing(string $tradeNo, array $snapshot): void
    {
        $key = $tradeNo;
        $this->redis()->hMset($key, [
            'status' => self::STATUS_PROCESSING,
            'snapshot' => json_encode($snapshot, \JSON_UNESCAPED_UNICODE),
            'created_at' => time(),
        ]);
        $this->redis()->expire($key, self::TTL);
    }

    /**
     * 标记订单创建成功.
     */
    public function markCreated(string $tradeNo): void
    {
        $this->redis()->hSet($tradeNo, 'status', self::STATUS_CREATED);
        $this->redis()->expire($tradeNo, 300);
    }

    /**
     * 标记订单创建失败.
     */
    public function markFailed(string $tradeNo, string $reason = ''): void
    {
        $this->redis()->hMset($tradeNo, [
            'status' => self::STATUS_FAILED,
            'error' => $reason,
        ]);
        $this->redis()->expire($tradeNo, 300);
    }

    /**
     * 查询订单状态.
     *
     * @return array{status: string, error: string}
     */
    public function getStatus(string $tradeNo): array
    {
        $data = $this->redis()->hGetAll($tradeNo);

        if (empty($data)) {
            return ['status' => '', 'error' => ''];
        }

        return [
            'status' => $data['status'] ?? '',
            'error' => $data['error'] ?? '',
        ];
    }

    /**
     * 每次操作前锁定 prefix，避免被其他服务的 setPrefix 污染.
     */
    private function redis(): ICache
    {
        $this->cache->setPrefix(self::PREFIX);
        return $this->cache;
    }
}
