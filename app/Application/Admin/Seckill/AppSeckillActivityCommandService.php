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

namespace App\Application\Admin\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillActivityInput;
use App\Domain\Trade\Seckill\Mapper\SeckillActivityMapper;
use App\Domain\Trade\Seckill\Service\DomainSeckillActivityService;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Hyperf\DbConnection\Db;

/**
 * 秒杀活动应用服务.
 *
 * 负责 DTO 到实体的转换，协调领域服务完成用例。
 */
final class AppSeckillActivityCommandService
{
    public function __construct(
        private readonly DomainSeckillActivityService $activityService,
        private readonly AppSeckillActivityQueryService $queryService
    ) {}

    /**
     * 创建秒杀活动.
     */
    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        $entity = SeckillActivityMapper::fromDto($dto);
        return Db::transaction(fn () => $this->activityService->create($entity));
    }

    /**
     * 更新秒杀活动.
     */
    public function update(SeckillActivityInput $dto): bool
    {
        $entity = $this->activityService->getEntity($dto->getId());
        if (! $entity) {
            throw new \RuntimeException('活动不存在');
        }
        $entity->update($dto);
        return Db::transaction(fn () => $this->activityService->update($entity));
    }

    /**
     * 删除秒杀活动.
     */
    public function delete(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        }
        return Db::transaction(fn () => $this->activityService->delete($id));
    }

    /**
     * 切换活动启用状态.
     */
    public function toggleEnabled(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        }
        return Db::transaction(fn () => $this->activityService->toggleEnabled($id));
    }

    /**
     * 取消活动.
     */
    public function cancel(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        }
        return Db::transaction(fn () => $this->activityService->cancel($id));
    }

    /**
     * 启动活动.
     */
    public function start(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        }
        return Db::transaction(fn () => $this->activityService->start($id));
    }

    /**
     * 结束活动.
     */
    public function end(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        }
        return Db::transaction(fn () => $this->activityService->end($id));
    }
}
