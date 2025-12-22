<?php

namespace Plugin\MineAdmin\Dictionary\Http\Controller;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Result;
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
