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

use App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface;
use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Transformer\DiyPageTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/diy/pages')]
#[Middleware(ApiSignatureMiddleware::class)]
final class DiyPageController extends AbstractController
{
    public function __construct(
        private readonly DiyPageQueryInterface $queryService,
        private readonly DiyPageTransformer $transformer,
    ) {}

    #[GetMapping(path: '{pageKey}')]
    public function show(string $pageKey, RequestInterface $request): Result
    {
        $pageType = (string) $request->input('page_type', 'miniprogram');

        return $this->success($this->transformer->transform(
            $this->queryService->published($pageKey, $pageType)
        ));
    }
}
