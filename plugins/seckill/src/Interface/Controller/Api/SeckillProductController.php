<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Interface\Controller\Api;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Plugin\Since\Seckill\Application\Api\AppApiSeckillProductQueryService;
use Plugin\Since\Seckill\Interface\Transformer\SeckillProductTransformer;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: '/api/v1/seckill/products')]
final class SeckillProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiSeckillProductQueryService $queryService,
        private readonly SeckillProductTransformer $transformer
    ) {}

    #[GetMapping(path: '{sessionId}/{spuId}')]
    public function show(int $sessionId, int $spuId): Result
    {
        $data = $this->queryService->getDetail($sessionId, $spuId);
        if ($data === null) { throw new BusinessException(ResultCode::NOT_FOUND, '秒杀商品不存在或活动已结束'); }
        return $this->successWithTransform($data, fn (array $d) => $this->transformer->transformDetail($d));
    }
}
