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

use App\Application\Api\Member\MemberAddressApiService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\MemberAddressRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/member/addresses')]
#[Middleware(TokenMiddleware::class)]
final class MemberAddressController extends AbstractController
{
    public function __construct(
        private readonly MemberAddressApiService $addressService,
        private readonly CurrentMember $currentMember,
        private readonly RequestInterface $request
    ) {}

    #[GetMapping(path: '')]
    public function index(): Result
    {
        $limit = (int) $this->request->query('limit', 20);
        $list = $this->addressService->list($this->currentMember->id(), max(1, min($limit, 50)));
        return $this->success(['list' => $list]);
    }

    #[GetMapping(path: '{id}')]
    public function show(int $id): Result
    {
        $address = $this->addressService->detail($this->currentMember->id(), $id);
        return $this->success($address);
    }

    #[PostMapping(path: '')]
    public function store(MemberAddressRequest $request): Result
    {
        $address = $this->addressService->create($this->currentMember->id(), $request->toDto());
        return $this->success($address, '新增成功');
    }

    #[PutMapping(path: '{id}')]
    public function update(MemberAddressRequest $request, int $id): Result
    {
        $address = $this->addressService->update($this->currentMember->id(), $id, $request->toDto());
        return $this->success($address, '更新成功');
    }

    #[DeleteMapping(path: '{id}')]
    public function destroy(int $id): Result
    {
        $this->addressService->delete($this->currentMember->id(), $id);
        return $this->success([], '删除成功');
    }

    #[PostMapping(path: '{id}/default')]
    public function markDefault(int $id): Result
    {
        $this->addressService->setDefault($this->currentMember->id(), $id);
        return $this->success([], '设置默认地址成功');
    }
}
