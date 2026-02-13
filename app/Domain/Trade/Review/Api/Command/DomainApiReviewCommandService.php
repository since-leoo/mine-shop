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

namespace App\Domain\Trade\Review\Api\Command;

use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Review\Contract\ReviewInput;
use App\Domain\Trade\Review\Mapper\ReviewMapper;
use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Review\Review;

/**
 * 小程序评价命令服务.
 */
final class DomainApiReviewCommandService extends IService
{
    public function __construct(
        private readonly ReviewRepository $repository,
    ) {}

    /**
     * 创建评价.
     *
     * 验证订单状态为 completed、订单项未评价、创建 Entity 并持久化
     */
    public function create(int $memberId, ReviewInput $dto): Review
    {
        // 1. 查找订单，不存在则抛出异常
        $order = Order::find($dto->getOrderId());
        if ($order === null) {
            throw new \RuntimeException('订单不存在');
        }

        // 2. 验证订单状态为 completed
        if ($order->status !== OrderStatus::COMPLETED->value) {
            throw new \RuntimeException('只能对已完成的订单进行评价');
        }

        // 3. 检查订单项是否已评价
        if ($this->repository->existsByOrderItemId($dto->getOrderItemId())) {
            throw new \RuntimeException('该订单项已评价');
        }

        // 4. 创建实体
        $entity = ReviewMapper::getNewEntity()->create($dto);

        // 5. 覆盖 memberId（使用认证用户的 ID，而非 DTO 中的值）
        $entity->setMemberId($memberId);

        // 6. 持久化
        return $this->repository->createFromEntity($entity);
    }
}
