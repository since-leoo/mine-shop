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

namespace App\Interface\Api\Controller\V1\GroupBuy;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use App\Application\Api\GroupBuy\AppApiGroupBuyProductQueryService;
use App\Interface\Api\Transformer\GroupBuy\GroupBuyProductTransformer;

#[Controller(prefix: '/api/v1/group-buy/products')]
final class GroupBuyProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiGroupBuyProductQueryService $queryService,
        private readonly GroupBuyProductTransformer $transformer,
        private readonly RequestInterface $request
    ) {}

    /**
     * 拼团商品列表（小程序促销页用）.
     */
    #[GetMapping(path: '')]
    public function index(): Result
    {
        $limit = (int) ($this->request->query('limit', 20));
        $data = $this->queryService->getPromotionList($limit);

        return $this->success($data);
    }

    /**
     * 获取某个拼团活动正在进行中的团列表（可参团）.
     */
    #[GetMapping(path: '{activityId}/groups')]
    public function groups(int $activityId): Result
    {
        $limit = (int) ($this->request->query('limit', 10));
        $list = $this->queryService->getOngoingGroups($activityId, $limit);

        return $this->success(['list' => $list]);
    }

    #[GetMapping(path: '{activityId}/{spuId}')]
    public function show(int $activityId, int $spuId): Result
    {
        $data = $this->queryService->getDetail($activityId, $spuId);
        if ($data === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '拼团商品不存在或活动已结束');
        }
        return $this->successWithTransform($data, fn (array $d) => $this->transformer->transformDetail($d));
    }
}
