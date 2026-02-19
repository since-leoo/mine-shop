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
use App\Domain\Trade\Seckill\Mapper\SeckillSessionMapper;
use App\Domain\Trade\Seckill\Service\DomainSeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\DbConnection\Db;

/**
 * 秒杀场次应用服务.
 *
 * 负责 DTO 到实体的转换，协调领域服务完成用例。
 */
final class AppSeckillSessionCommandService
{
    public function __construct(
        private readonly DomainSeckillSessionService $sessionService,
        private readonly AppSeckillSessionQueryService $queryService
    ) {}

    /**
     * 创建秒杀场次.
     */
    public function create(SeckillSessionInput $dto): SeckillSession
    {
        $entity = SeckillSessionMapper::fromDto($dto);
        return Db::transaction(fn () => $this->sessionService->create($entity));
    }

    /**
     * 更新秒杀场次.
     */
    public function update(SeckillSessionInput $dto): bool
    {
        $entity = $this->sessionService->getEntity($dto->getId());
        if (! $entity) {
            throw new \RuntimeException('场次不存在');
        }
        $entity->update($dto);
        return Db::transaction(fn () => $this->sessionService->update($entity));
    }

    /**
     * 删除秒杀场次.
     */
    public function delete(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('场次不存在');
        }
        return Db::transaction(fn () => $this->sessionService->delete($id));
    }

    /**
     * 切换场次启用状态.
     */
    public function toggleStatus(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('场次不存在');
        }
        return Db::transaction(fn () => $this->sessionService->toggleStatus($id));
    }
}
