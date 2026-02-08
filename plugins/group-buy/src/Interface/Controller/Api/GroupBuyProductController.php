<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Interface\Controller\Api;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Plugin\Since\GroupBuy\Application\Api\AppApiGroupBuyProductQueryService;
use Plugin\Since\GroupBuy\Interface\Transformer\GroupBuyProductTransformer;

#[Controller(prefix: '/api/v1/group-buy/products')]
final class GroupBuyProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiGroupBuyProductQueryService $queryService,
        private readonly GroupBuyProductTransformer $transformer
    ) {}

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
