<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Application\Admin;

use Plugin\Since\Seckill\Domain\Contract\SeckillActivityInput;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillActivity;
use Plugin\Since\Seckill\Domain\Service\DomainSeckillActivityService;
use Hyperf\DbConnection\Db;

final class AppSeckillActivityCommandService
{
    public function __construct(
        private readonly DomainSeckillActivityService $activityService,
        private readonly AppSeckillActivityQueryService $queryService
    ) {}

    public function create(SeckillActivityInput $dto): SeckillActivity { return Db::transaction(fn () => $this->activityService->create($dto)); }
    public function update(SeckillActivityInput $dto): bool { if (!$this->queryService->find($dto->getId())) { throw new \RuntimeException('活动不存在'); } return Db::transaction(fn () => $this->activityService->update($dto)); }
    public function delete(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('活动不存在'); } return Db::transaction(fn () => $this->activityService->delete($id)); }
    public function toggleEnabled(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('活动不存在'); } return Db::transaction(fn () => $this->activityService->toggleEnabled($id)); }
    public function cancel(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('活动不存在'); } return Db::transaction(fn () => $this->activityService->cancel($id)); }
    public function start(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('活动不存在'); } return Db::transaction(fn () => $this->activityService->start($id)); }
    public function end(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('活动不存在'); } return Db::transaction(fn () => $this->activityService->end($id)); }
}
