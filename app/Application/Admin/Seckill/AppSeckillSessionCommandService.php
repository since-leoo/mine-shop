<?php

declare(strict_types=1);

namespace App\Application\Admin\Seckill;

use Hyperf\DbConnection\Db;
use App\Domain\Trade\Seckill\Contract\SeckillSessionInput;
use App\Domain\Trade\Seckill\Service\DomainSeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;

final class AppSeckillSessionCommandService
{
    public function __construct(private readonly DomainSeckillSessionService $sessionService, private readonly AppSeckillSessionQueryService $queryService) {}

    public function create(SeckillSessionInput $dto): SeckillSession
    {
        return Db::transaction(fn () => $this->sessionService->create($dto));
    }

    public function update(SeckillSessionInput $dto): bool
    {
        if (! $this->queryService->find($dto->getId())) {
            throw new \RuntimeException('场次不存在');
        } return Db::transaction(fn () => $this->sessionService->update($dto));
    }

    public function delete(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('场次不存在');
        } return Db::transaction(fn () => $this->sessionService->delete($id));
    }

    public function toggleStatus(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('场次不存在');
        } return Db::transaction(fn () => $this->sessionService->toggleStatus($id));
    }
}
