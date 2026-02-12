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

use App\Application\Api\Member\AppApiMemberAuthCommandService;
use App\Application\Api\Member\AppApiMemberCenterQueryService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\PhoneAuthorizeRequest;
use App\Interface\Api\Request\V1\ProfileAuthorizeRequest;
use App\Interface\Api\Request\V1\ProfileUpdateRequest;
use App\Interface\Api\Transformer\MemberCenterTransformer;
use App\Interface\Api\Transformer\MemberProfileTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller(prefix: '/api/v1/member')]
#[Middleware(TokenMiddleware::class)]
final class MemberController extends AbstractController
{
    public function __construct(
        private readonly AppApiMemberCenterQueryService $memberCenterService,
        private readonly MemberProfileTransformer $profileTransformer,
        private readonly MemberCenterTransformer $centerTransformer,
        private readonly CurrentMember $currentMember,
        private readonly AppApiMemberAuthCommandService $memberAuthService
    ) {}

    #[GetMapping(path: 'profile')]
    public function profile(): Result
    {
        $member = $this->memberCenterService->profile($this->currentMember->id());
        return $this->success(['member' => $this->profileTransformer->transform($member)], '获取成功');
    }

    #[GetMapping(path: 'center')]
    public function center(): Result
    {
        $data = $this->memberCenterService->overview($this->currentMember->id());
        $profile = $this->profileTransformer->transform($data['member']);

        $overview = $this->centerTransformer->transform(
            $profile,
            $data['orderCounts'],
            $data['couponCount'],
            $data['servicePhone']
        );

        return $this->success($overview, '获取成功');
    }

    #[PostMapping(path: 'phone/bind')]
    public function bindPhone(PhoneAuthorizeRequest $request): Result
    {
        $payload = $request->validated();
        $result = $this->memberAuthService->bindPhoneNumber($this->currentMember->id(), $payload['code']);
        return $this->success($result, '手机号授权成功');
    }

    #[PostMapping(path: 'profile/authorize')]
    public function authorizeProfile(ProfileAuthorizeRequest $request): Result
    {
        $this->memberAuthService->authorizeProfile($this->currentMember->id(), $request->toDto());
        return $this->success([], '头像昵称授权成功');
    }

    #[PostMapping(path: 'profile/update')]
    public function updateProfile(ProfileUpdateRequest $request): Result
    {
        $this->memberAuthService->updateProfile($this->currentMember->id(), $request->toDto());
        return $this->success([], '资料修改成功');
    }
}
