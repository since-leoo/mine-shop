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

namespace App\Domain\Trade\Review\Mapper;

use App\Domain\Trade\Review\Entity\ReviewEntity;
use App\Infrastructure\Model\Review\Review;

class ReviewMapper
{
    public static function getNewEntity(): ReviewEntity
    {
        return new ReviewEntity();
    }

    public static function fromModel(Review $model): ReviewEntity
    {
        $entity = new ReviewEntity();
        $entity->setId((int) $model->id);
        $entity->setOrderId((int) $model->order_id);
        $entity->setOrderItemId((int) $model->order_item_id);
        $entity->setProductId((int) $model->product_id);
        $entity->setSkuId((int) $model->sku_id);
        $entity->setMemberId((int) $model->member_id);
        $entity->setRating((int) $model->rating);
        $entity->setContent($model->content);
        $entity->setImages($model->images);
        $entity->setIsAnonymous((bool) $model->is_anonymous);
        $entity->setStatus($model->status);
        $entity->setAdminReply($model->admin_reply);
        $entity->setReplyTime($model->reply_time?->format('Y-m-d H:i:s'));
        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(ReviewEntity $entity): array
    {
        return $entity->toArray();
    }
}
