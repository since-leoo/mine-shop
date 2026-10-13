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

namespace Plugin\ProductReview\Domain\Trade\Review\Api\Command;

use App\Domain\Trade\Review\Contract\ReviewInput;
use Plugin\ProductReview\Domain\Trade\Review\Mapper\ReviewMapper;
use Plugin\ProductReview\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Abstract\IService;
use App\Domain\Infrastructure\Plugin\Contract\ReviewEligibilityInterface;
use Plugin\ProductReview\Infrastructure\Model\Review\Review;

/**
 * 小程序评价命令服务。
 */
final class DomainApiReviewCommandService extends IService
{
    public function __construct(
        private readonly ReviewRepository $repository,
        private readonly ReviewEligibilityInterface $eligibility,
    ) {}

    /**
     * 创建评价。
     */
    public function create(int $memberId, ReviewInput $dto): Review
    {
        $item = $this->eligibility->eligibleItem($memberId, (int) $dto->getOrderId(), (int) $dto->getOrderItemId());

        if ($this->repository->existsByOrderItemId((int) $dto->getOrderItemId())) {
            throw new \RuntimeException('该订单商品已评价');
        }

        $entity = ReviewMapper::getNewEntity()->create($dto);
        $entity->setMemberId($memberId);
        $entity->setProductId($item['product_id']);
        $entity->setSkuId($item['sku_id']);
        $entity->setOrderId($item['order_id']);
        $entity->setOrderItemId($item['order_item_id']);

        return $this->repository->createFromEntity($entity);
    }
}
