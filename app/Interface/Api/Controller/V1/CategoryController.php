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

use App\Application\Api\Product\AppApiCategoryQueryService;
use App\Interface\Api\Transformer\CategoryTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: '/api/v1/categories')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly AppApiCategoryQueryService $service,
        private readonly CategoryTransformer $transformer
    ) {}

    #[GetMapping(path: '')]
    public function index(): Result
    {
        $categories = $this->service->tree();
        return $this->success(['list' => $this->transformer->transformTree($categories)]);
    }
}
