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

namespace App\Application\Commad;

use App\Application\Query\SeckillActivityQueryService;
use App\Domain\Seckill\Contract\SeckillActivityInput;
use App\Domain\Seckill\Service\SeckillActivityService;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Hyperf\DbConnection\Db;

/**
 * 秒杀活动命令服务：处理所有写操作.
 */
final class SeckillActivityCommandService
{
    public function __construct(
        private readonly SeckillActivityService $activityService,
        private readonly SeckillActivityQueryService $queryService
    ) {}

    /**
     * 创建活动.
     */
    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        return Db::transaction(fn () => $this->activityService->create($dto));
    }

    /**
     * 更新活动.
     */
    public function update(SeckillActivityInput $dto): bool
    {
        $activity = $this->queryService->find($dto->getId());
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        return Db::transaction(fn () => $this->activityService->update($dto));
    }

    /**
     * 删除活动.
     */
    public function delete(int $id): bool
    {
        $activity = $this->queryService->find($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        return Db::transaction(fn () => $this->activityService->delete($id));
    }

    /**
     * 切换活动启用状态.
     */
    public function toggleEnabled(int $id): bool
    {
        $activity = $this->queryService->find($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        return Db::transaction(fn () => $this->activityService->toggleEnabled($id));
    }

    /**
     * 取消活动.
     */
    public function cancel(int $id): bool
    {
        $activity = $this->queryService->find($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        return Db::transaction(fn () => $this->activityService->cancel($id));
    }

    /**
     * 开始活动.
     */
    public function start(int $id): bool
    {
        $activity = $this->queryService->find($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        return Db::transaction(fn () => $this->activityService->start($id));
    }

    /**
     * 结束活动.
     */
    public function end(int $id): bool
    {
        $activity = $this->queryService->find($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        return Db::transaction(fn () => $this->activityService->end($id));
    }
}
