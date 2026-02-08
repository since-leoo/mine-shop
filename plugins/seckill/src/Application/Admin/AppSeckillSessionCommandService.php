<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Application\Admin;

use Plugin\Since\Seckill\Domain\Contract\SeckillSessionInput;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillSession;
use Plugin\Since\Seckill\Domain\Service\DomainSeckillSessionService;
use Hyperf\DbConnection\Db;

final class AppSeckillSessionCommandService
{
    public function __construct(private readonly DomainSeckillSessionService $sessionService, private readonly AppSeckillSessionQueryService $queryService) {}

    public function create(SeckillSessionInput $dto): SeckillSession { return Db::transaction(fn () => $this->sessionService->create($dto)); }
    public function update(SeckillSessionInput $dto): bool { if (!$this->queryService->find($dto->getId())) { throw new \RuntimeException('场次不存在'); } return Db::transaction(fn () => $this->sessionService->update($dto)); }
    public function delete(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('场次不存在'); } return Db::transaction(fn () => $this->sessionService->delete($id)); }
    public function toggleStatus(int $id): bool { if (!$this->queryService->find($id)) { throw new \RuntimeException('场次不存在'); } return Db::transaction(fn () => $this->sessionService->toggleStatus($id)); }
}
