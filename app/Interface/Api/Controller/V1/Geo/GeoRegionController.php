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

namespace App\Interface\Api\Controller\V1\Geo;

use App\Application\Api\Geo\AppApiGeoRegionQueryService;
use App\Interface\Api\Transformer\GeoRegionTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/geo/regions')]
final class GeoRegionController extends AbstractController
{
    public function __construct(
        private readonly AppApiGeoRegionQueryService $queryService,
        private readonly GeoRegionTransformer $transformer
    ) {}

    #[GetMapping(path: '')]
    public function index(RequestInterface $request): Result
    {
        $parentCode = (string) $request->input('parent_code', '0');
        $limit = (int) $request->input('limit', 200);
        $result = $this->queryService->children($parentCode, $limit);

        return $this->success([
            'version' => $result['version'],
            'updated_at' => $result['updated_at'],
            'parent_code' => $result['parent_code'],
            'list' => array_map(fn (array $item) => $this->transformer->transformRegion($item), $result['list']),
        ]);
    }
}
