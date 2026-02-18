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
use App\Domain\Trade\Seckill\Service\DomainSeckillActivityService;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Hyperf\DbConnection\Db;

final class AppSeckillActivityCommandService
{
    public function __construct(
        private readonly DomainSeckillActivityService $activityService,
        private readonly AppSeckillActivityQueryService $queryService
    ) {}

    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        return Db::transaction(fn () => $this->activityService->create($dto));
    }

    public function update(SeckillActivityInput $dto): bool
    {
        if (! $this->queryService->find($dto->getId())) {
            throw new \RuntimeException('活动不存在');
        } return Db::transaction(fn () => $this->activityService->update($dto));
    }

    public function delete(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        } return Db::transaction(fn () => $this->activityService->delete($id));
    }

    public function toggleEnabled(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        } return Db::transaction(fn () => $this->activityService->toggleEnabled($id));
    }

    public function cancel(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        } return Db::transaction(fn () => $this->activityService->cancel($id));
    }

    public function start(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        } return Db::transaction(fn () => $this->activityService->start($id));
    }

    public function end(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('活动不存在');
        } return Db::transaction(fn () => $this->activityService->end($id));
    }
}
