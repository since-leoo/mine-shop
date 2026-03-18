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
use App\Infrastructure\Model\Order\OrderItem;
use App\Infrastructure\Model\Review\Review;

/**
 * 小程序评价命令服务。
 */
final class DomainApiReviewCommandService extends IService
{
    public function __construct(
        private readonly ReviewRepository $repository,
    ) {}

    /**
     * 创建评价。
     */
    public function create(int $memberId, ReviewInput $dto): Review
    {
        $order = Order::find($dto->getOrderId());
        if ($order === null) {
            throw new \RuntimeException('订单不存在');
        }

        if ((int) $order->member_id !== $memberId) {
            throw new \RuntimeException('无权评价该订单');
        }

        if ($order->status !== OrderStatus::COMPLETED->value) {
            throw new \RuntimeException('只能对已完成的订单进行评价');
        }

        $orderItem = OrderItem::find($dto->getOrderItemId());
        if ($orderItem === null || (int) $orderItem->order_id !== (int) $order->id) {
            throw new \RuntimeException('订单商品不存在');
        }

        if ($this->repository->existsByOrderItemId((int) $dto->getOrderItemId())) {
            throw new \RuntimeException('该订单商品已评价');
        }

        $entity = ReviewMapper::getNewEntity()->create($dto);
        $entity->setMemberId($memberId);
        $entity->setProductId((int) $orderItem->product_id);
        $entity->setSkuId((int) $orderItem->sku_id);
        $entity->setOrderId((int) $order->id);
        $entity->setOrderItemId((int) $orderItem->id);

        return $this->repository->createFromEntity($entity);
    }
}
