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

namespace App\Interface\Api\Controller\V1\Seckill;

use App\Application\Api\Seckill\AppApiSeckillProductQueryService;
use App\Infrastructure\Error\SeckillError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Request\V1\Seckill\SeckillPromotionRequest;
use App\Interface\Api\Transformer\Seckill\SeckillProductTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;

#[Controller(prefix: '/api/v1/seckill/products')]
#[Middleware(ApiSignatureMiddleware::class)]
final class SeckillProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiSeckillProductQueryService $queryService,
        private readonly SeckillProductTransformer $transformer
    ) {}

    /**
     * 秒杀商品列表（小程序促销页用）.
     */
    #[GetMapping(path: '')]
    public function index(SeckillPromotionRequest $request): Result
    {
        try {
            $data = $this->queryService->getPromotionList($request->toDto());
        } catch (\DomainException $exception) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, $exception->getMessage(), errorCode: $exception->getCode() ?: null);
        }
        return $this->success($this->transformer->transformPromotionList($data));
    }

    #[GetMapping(path: '{sessionId}/{spuId}')]
    public function show(int $sessionId, int $spuId): Result
    {
        $data = $this->queryService->getDetail($sessionId, $spuId);
        if ($data === null) {
            throw new BusinessException(SeckillError::PRODUCT_OR_ACTIVITY_UNAVAILABLE, resultCode: ResultCode::NOT_FOUND);
        }
        return $this->successWithTransform($data, fn (array $d) => $this->transformer->transformDetail($d));
    }
}
