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

namespace Plugin\Since\Coupon\Interface\Controller\Api;

use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Plugin\Since\Coupon\Application\Api\AppApiCouponQueryService;
use Plugin\Since\Coupon\Interface\Request\Api\CouponAvailableRequest;
use Plugin\Since\Coupon\Interface\Transformer\CouponTransformer;

#[Controller(prefix: '/api/v1/coupons')]
final class CouponController extends AbstractController
{
    public function __construct(
        private readonly AppApiCouponQueryService $queryService,
        private readonly CouponTransformer $transformer,
        private readonly CurrentMember $currentMember,
    ) {}

    #[GetMapping(path: 'available')]
    public function available(CouponAvailableRequest $request): Result
    {
        $payload = $request->validated();
        $limit = (int) ($payload['limit'] ?? 20);
        unset($payload['limit']);
        $memberId = $this->currentMember->id() ?: null;

        $result = $this->queryService->available($payload, $memberId, $limit);

        $list = $result['collection']->map(function ($coupon) use ($result) {
            $received = $result['receivedMap'][(int) $coupon->id] ?? 0;
            return $this->transformer->transformListItem($coupon, $received);
        })->toArray();

        return $this->success(['list' => $list, 'total' => $result['total']]);
    }

    #[GetMapping(path: '{id}')]
    public function show(int $id): Result
    {
        $result = $this->queryService->detail($id, null);

        return $this->success([
            'detail' => $this->transformer->transformMiniDetail($result['coupon']),
        ]);
    }
}
