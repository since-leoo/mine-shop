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

namespace App\Interface\Api\Controller\V1\Common;

use App\Application\Api\Content\AppApiDiyPageQueryService;
use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Transformer\DiyPageTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;

#[Controller(prefix: '/api/v1/diy/preview')]
#[Middleware(ApiSignatureMiddleware::class)]
final class DiyPreviewController extends AbstractController
{
    public function __construct(
        private readonly AppApiDiyPageQueryService $queryService,
        private readonly DiyPageTransformer $transformer,
    ) {}

    #[GetMapping(path: '{token}')]
    public function show(string $token): Result
    {
        return $this->success($this->transformer->transform(
            $this->queryService->preview($token)
        ));
    }
}
