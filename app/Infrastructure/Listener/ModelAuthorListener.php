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

namespace App\Infrastructure\Listener;

use Hyperf\Context\Context;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Events\Updating;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 自动填充 created_by 和 updated_by 字段的监听器.
 *
 * 监听所有 Model 的 creating 和 updating 事件，
 * 如果 Model 有 created_by/updated_by 字段，则自动填充当前用户ID。
 */
#[Listener]
class ModelAuthorListener implements ListenerInterface
{
    /**
     * 需要自动填充 author 字段的表.
     */
    private const AUTHOR_TABLES = [
        'products',
        'categories',
        'brands',
        'orders',
        'coupons',
        'shipping_templates',
        'group_buys',
        'seckill_activities',
        'reviews',
        'member_levels',
        'member_tags',
    ];

    public function listen(): array
    {
        return [
            Creating::class,
            Updating::class,
        ];
    }

    public function process(object $event): void
    {
        $model = $event->getModel();
        $table = $model->getTable();

        // 检查是否是需要自动填充的表
        if (! $this->shouldFillAuthor($table)) {
            return;
        }

        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        if ($event instanceof Creating) {
            if ($model->isFillable('created_by') && empty($model->created_by)) {
                $model->created_by = $userId;
            }
        }

        if ($event instanceof Updating) {
            if ($model->isFillable('updated_by')) {
                $model->updated_by = $userId;
            }
        }
    }

    /**
     * 检查表是否需要自动填充 author 字段.
     */
    private function shouldFillAuthor(string $table): bool
    {
        // 移除表前缀进行匹配
        $tableName = preg_replace('/^mall_/', '', $table);

        return in_array($tableName, self::AUTHOR_TABLES, true);
    }

    /**
     * 从上下文中获取当前用户ID.
     */
    private function getCurrentUserId(): ?int
    {
        $user = Context::get('current_user');

        return $user?->id;
    }
}
