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

namespace App\Interface\Api\Controller\V1;

use App\Application\Api\Product\AppApiProductQueryService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Api\Request\V1\ProductListRequest;
use App\Interface\Api\Transformer\ProductTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: '/api/v1/products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly AppApiProductQueryService $queryService,
        private readonly ProductTransformer $transformer
    ) {}

    #[GetMapping(path: '')]
    public function index(ProductListRequest $request): Result
    {
        $payload = $request->validated();
        $page = (int) ($payload['page'] ?? 1);
        $pageSize = (int) ($payload['page_size'] ?? 20);
        unset($payload['page'], $payload['page_size']);

        $result = $this->queryService->page($payload, $page, $pageSize);

        return $this->successWithArrayList(
            $result['list'],
            fn (array $product) => $this->transformer->transformListItem($product),
            $result['total']
        );
    }

    #[GetMapping(path: '{id}')]
    public function show(int $id): Result
    {
        $detail = $this->queryService->findById($id);
        if ($detail === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '商品不存在');
        }

        return $this->successWithTransform($detail, fn (array $product) => $this->transformer->transformDetail($product));
    }
}
