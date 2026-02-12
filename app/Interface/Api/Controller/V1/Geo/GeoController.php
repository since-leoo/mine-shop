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

use App\Domain\Infrastructure\Geo\Service\GeoQueryService;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/common/geo')]
final class GeoController extends AbstractController
{
    public function __construct(private readonly GeoQueryService $service) {}

    #[GetMapping(path: 'pcas')]
    public function pcas(): Result
    {
        return $this->success($this->service->getCascadeTree());
    }

    #[GetMapping(path: 'search')]
    public function search(RequestInterface $request): Result
    {
        $keyword = (string) $request->input('keyword', '');
        $limit = (int) $request->input('limit', 20);
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $this->success(['list' => []]);
        }

        $limit = max(1, min(50, $limit));
        $result = $this->service->search($keyword, $limit);

        return $this->success($result);
    }
}
