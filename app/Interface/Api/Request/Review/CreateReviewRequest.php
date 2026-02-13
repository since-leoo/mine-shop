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

namespace App\Interface\Api\Request\Review;

use App\Domain\Trade\Review\Contract\ReviewInput;
use App\Interface\Admin\Dto\Review\ReviewDto;
use App\Interface\Common\Request\BaseRequest;

final class CreateReviewRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'min:1', 'max:1000'],
            'images' => ['nullable', 'array', 'max:9'],
            'images.*' => ['string', 'url'],
            'is_anonymous' => ['nullable', 'boolean'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rating' => '评分',
            'content' => '评价内容',
            'images' => '评价图片',
            'images.*' => '图片地址',
            'is_anonymous' => '匿名评价',
            'order_id' => '订单',
            'order_item_id' => '订单项',
        ];
    }

    /**
     * 转换为 ReviewDto.
     */
    public function toDto(): ReviewInput
    {
        $params = $this->validated();

        $dto = new ReviewDto();
        $dto->rating = (int) $params['rating'];
        $dto->content = $params['content'];
        $dto->images = $params['images'] ?? null;
        $dto->isAnonymous = $params['is_anonymous'] ?? false;
        $dto->orderId = (int) $params['order_id'];
        $dto->orderItemId = (int) $params['order_item_id'];

        return $dto;
    }
}
