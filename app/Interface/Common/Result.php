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

namespace App\Interface\Common;

use Hyperf\Contract\Arrayable;

/**
 * @template T
 */
class Result implements Arrayable
{
    /**
     * @param T $data
     */
    public function __construct(
        public ResultCode $code = ResultCode::SUCCESS,
        public ?string $message = null,
        public mixed $data = [],
        public ?int $errorCode = null,
    ) {
        if ($this->message === null) {
            $this->message = ResultCode::getMessage($this->code->value);
        }
    }

    public function toArray(): array
    {
        $result = [
            'code' => $this->code->value,
            'message' => $this->message,
            'data' => $this->data,
        ];
        if ($this->errorCode !== null) {
            $result['error_code'] = $this->errorCode;
        }
        return $result;
    }
}
