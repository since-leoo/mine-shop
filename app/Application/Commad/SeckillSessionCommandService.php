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

use App\Application\Query\SeckillSessionQueryService;
use App\Domain\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Seckill\Service\SeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * 秒杀场次命令服务：处理所有写操作.
 */
final class SeckillSessionCommandService
{
    public function __construct(
        private readonly SeckillSessionService $sessionService,
        private readonly SeckillSessionQueryService $queryService
    ) {}

    /**
     * 创建场次.
     */
    public function create(SeckillSessionEntity $entity): SeckillSession
    {
        return $this->sessionService->create($entity);
    }

    /**
     * 更新场次.
     */
    public function update(SeckillSessionEntity $entity): bool
    {
        $session = $this->queryService->find($entity->getId());
        $session || throw new \InvalidArgumentException('场次不存在');

        return $this->sessionService->update($entity);
    }

    /**
     * 删除场次.
     */
    public function delete(int $id): bool
    {
        $session = $this->queryService->find($id);
        $session || throw new \InvalidArgumentException('场次不存在');

        return $this->sessionService->delete($id);
    }

    /**
     * 切换场次状态.
     */
    public function toggleStatus(int $id): bool
    {
        $session = $this->queryService->find($id);
        $session || throw new \InvalidArgumentException('场次不存在');

        return $this->sessionService->toggleStatus($id);
    }
}
