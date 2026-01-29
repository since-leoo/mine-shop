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

namespace Plugin\MineAdmin\Dictionary\Http\Controller;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Hyperf\Swagger\Annotation\Get;
use Plugin\MineAdmin\Dictionary\Service\DictionaryTypeService as Service;

#[OA\Tag('所有字典')]
#[OA\HyperfServer('http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
class DictionaryAllController extends AbstractController
{
    public function __construct(
        private readonly Service $service,
    ) {}

    #[Get(
        path: '/admin/data_center/getAllDictionary',
    )]
    public function getAllDictionary(): Result
    {
        return $this->success(
            $this->service->getAllDictionary()
        );
    }
}
