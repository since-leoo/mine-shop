<?php

declare(strict_types=1);

namespace App\Interface\Api\Controller\V1\Seckill;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use App\Application\Api\Seckill\AppApiSeckillProductQueryService;
use App\Interface\Api\Transformer\Seckill\SeckillProductTransformer;

#[Controller(prefix: '/api/v1/seckill/products')]
final class SeckillProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiSeckillProductQueryService $queryService,
        private readonly SeckillProductTransformer $transformer,
        private readonly RequestInterface $request
    ) {}

    /**
     * 秒杀商品列表（小程序促销页用）.
     */
    #[GetMapping(path: '')]
    public function index(): Result
    {
        $limit = (int) ($this->request->query('limit', 20));
        $data = $this->queryService->getPromotionList($limit);

        return $this->success($data);
    }

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
