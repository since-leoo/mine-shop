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

use App\Domain\Trade\Seckill\Contract\SeckillSessionInput;
use App\Domain\Trade\Seckill\Service\DomainSeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\DbConnection\Db;

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
