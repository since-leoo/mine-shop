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

namespace App\Interface\Admin\Request\Review;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

class ReviewReplyRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function replyRules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => '回复内容',
        ];
    }
}
