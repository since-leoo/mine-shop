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

namespace App\Interface\Common\Controller;

use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\Contract\LengthAwarePaginatorInterface;

abstract class AbstractController
{
    protected function success(mixed $data = [], ?string $message = null, mixed $_extra = null): Result
    {
        return new Result(ResultCode::SUCCESS, $message, $data);
    }

    protected function fail(?string $message = null, mixed $data = []): Result
    {
        return new Result(ResultCode::FAIL, $message, $data);
    }

    protected function error(?string $message = null, mixed $data = []): Result
    {
        return new Result(ResultCode::FAIL, $message, $data);
    }

    protected function json(ResultCode $code, mixed $data = [], ?string $message = null): Result
    {
        return new Result($code, $message, $data);
    }

    /**
     * 使用 callable 转换单个资源.
     */
    protected function successWithTransform(mixed $item, callable $transformer, ?string $message = null): Result
    {
        return $this->success($transformer($item), $message);
    }

    /**
     * 使用 callable 转换集合（数组/Collection）.
     */
    protected function successWithArrayList(
        iterable $items,
        callable $transformer,
        ?int $total = null,
        ?string $message = null
    ): Result {
        $list = [];
        foreach ($items as $item) {
            $list[] = $transformer($item);
        }

        $data = ['list' => $list];
        if ($total !== null) {
            $data['total'] = $total;
        }

        return $this->success($data, $message);
    }

    /**
     * 使用 callable 转换分页资源.
     */
    protected function successWithPaginator(
        LengthAwarePaginatorInterface $paginator,
        callable $transformer,
        ?string $message = null
    ): Result {
        $list = [];
        foreach ($paginator->items() as $item) {
            $list[] = $transformer($item);
        }

        return $this->success([
            'list' => $list,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], $message);
    }
}
