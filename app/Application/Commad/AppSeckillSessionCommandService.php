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

use App\Application\Query\AppSeckillSessionQueryService;
use App\Domain\Seckill\Contract\SeckillSessionInput;
use App\Domain\Seckill\Service\DomainSeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\DbConnection\Db;

/**
 * 秒杀场次命令服务：处理所有写操作.
 */
final class AppSeckillSessionCommandService
{
    public function __construct(
        private readonly DomainSeckillSessionService $sessionService,
        private readonly AppSeckillSessionQueryService $queryService
    ) {}

    /**
     * 创建场次.
     */
    public function create(SeckillSessionInput $dto): SeckillSession
    {
        return Db::transaction(fn () => $this->sessionService->create($dto));
    }

    /**
     * 更新场次.
     */
    public function update(SeckillSessionInput $dto): bool
    {
        $session = $this->queryService->find($dto->getId());
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }

        return Db::transaction(fn () => $this->sessionService->update($dto));
    }

    /**
     * 删除场次.
     */
    public function delete(int $id): bool
    {
        $session = $this->queryService->find($id);
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }

        return Db::transaction(fn () => $this->sessionService->delete($id));
    }

    /**
     * 切换场次状态.
     */
    public function toggleStatus(int $id): bool
    {
        $session = $this->queryService->find($id);
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }

        return Db::transaction(fn () => $this->sessionService->toggleStatus($id));
    }
}
