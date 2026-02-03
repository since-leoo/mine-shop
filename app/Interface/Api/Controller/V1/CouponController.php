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

namespace App\Interface\Api\Controller\V1;

use App\Application\Api\Coupon\CouponQueryApiService;
use App\Interface\Api\Request\V1\CouponAvailableRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: '/api/v1/coupons')]
final class CouponController extends AbstractController
{
    public function __construct(private readonly CouponQueryApiService $queryService) {}

    #[GetMapping(path: 'available')]
    public function available(CouponAvailableRequest $request): Result
    {
        $payload = $request->validated();
        $limit = (int) ($payload['limit'] ?? 20);
        unset($payload['limit']);

        $data = $this->queryService->available($payload, null, $limit);

        return $this->success($data);
    }

    #[GetMapping(path: '{id}')]
    public function show(int $id): Result
    {
        $data = $this->queryService->detail($id, null);

        return $this->success($data);
    }
}
