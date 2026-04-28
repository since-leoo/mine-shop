<?php

declare(strict_types=1);

namespace App\Interface\Api\Controller\V1\Seckill;

use App\Application\Api\Seckill\AppApiSeckillSessionQueryService;
use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Transformer\Seckill\SeckillSessionTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/seckill/sessions')]
#[Middleware(ApiSignatureMiddleware::class)]
final class SeckillSessionController extends AbstractController
{
    public function __construct(
        private readonly AppApiSeckillSessionQueryService $queryService,
        private readonly SeckillSessionTransformer $transformer,
        private readonly RequestInterface $request,
    ) {}

    #[GetMapping(path: '')]
    public function index(): Result
    {
        $activityId = (int) $this->request->query('activityId', 0);
        $sessions = $this->queryService->getSessionList($activityId > 0 ? $activityId : null);
        return $this->success($this->transformer->transformList($sessions));
    }
}
