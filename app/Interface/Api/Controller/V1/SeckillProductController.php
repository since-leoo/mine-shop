<?php

declare(strict_types=1);

namespace App\Interface\Api\Controller\V1;

use App\Application\Api\Product\AppApiSeckillProductQueryService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Api\Transformer\SeckillProductTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: '/api/v1/seckill/products')]
final class SeckillProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiSeckillProductQueryService $queryService,
        private readonly SeckillProductTransformer $transformer
    ) {}

    /**
     * 秒杀商品详情.
     *
     * GET /api/v1/seckill/products/{sessionId}/{spuId}
     */
    #[GetMapping(path: '{sessionId}/{spuId}')]
    public function show(int $sessionId, int $spuId): Result
    {
        $data = $this->queryService->getDetail($sessionId, $spuId);
        if ($data === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '秒杀商品不存在或活动已结束');
        }

        return $this->successWithTransform($data, fn (array $d) => $this->transformer->transformDetail($d));
    }
}
