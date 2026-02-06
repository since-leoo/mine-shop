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

namespace App\Domain\Member\Service;

use App\Domain\Member\Contract\MemberInput;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Mapper\MemberMapper;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Repository\MemberTagRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\Member;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;
use Plugin\Wechat\Interfaces\MiniAppInterface;

/**
 * 会员领域服务.
 */
final class MemberService extends IService
{
    public function __construct(
        protected readonly MemberRepository $repository,
        private readonly MemberTagRepository $memberTagRepository,
        private readonly MiniAppInterface $miniApp,
    ) {}

    /**
     * 创建会员.
     */
    public function create(MemberInput $dto): Member
    {
        // 1. 通过 Mapper 获取新实体
        $entity = MemberMapper::getNewEntity();

        // 2. 调用实体的 create 行为方法
        $entity->create($dto);

        // 3. 调用仓储持久化
        $member = $this->repository->save($entity);

        // 4. 同步标签
        if ($entity->getTagIds() !== []) {
            $this->applyTags($member->id, $entity->getTagIds());
        }

        return $member;
    }

    /**
     * 更新会员档案.
     */
    public function update(MemberInput $dto): Member
    {
        // 1. 通过仓储获取 Model
        $model = $this->repository->findById($dto->getId());
        if (! $model) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        // 2. 通过 Mapper 将 Model 转换为 Entity
        $entity = MemberMapper::fromModel($model);

        // 3. 调用实体的 update 行为方法
        $entity->update($dto);

        // 4. 持久化修改
        $this->repository->updateEntity($entity);

        return $model->refresh();
    }

    /**
     * 更新会员状态.
     */
    public function updateStatus(int $memberId, string $status): Member
    {
        // 1. 获取实体
        $entity = $this->getEntity($memberId);

        // 2. 调用实体的 updateStatus 行为方法
        $entity->updateStatus($status);

        // 3. 持久化修改
        $this->repository->updateEntity($entity);

        /* @var Member $model */
        return $this->repository->findById($memberId);
    }

    /**
     * 同步会员标签.
     */
    public function syncTags(int $memberId, array $tagIds): void
    {
        $this->ensureMemberExists($memberId);
        $this->applyTags($memberId, $tagIds);
    }

    /**
     * 获取会员实体.
     *
     * 通过 ID 获取 Model，然后通过 Mapper 转换为 Entity.
     * 用于需要调用实体行为方法的场景.
     *
     * @param int $memberId 会员ID
     * @return MemberEntity 会员实体对象
     * @throws BusinessException 当会员不存在时
     */
    public function getEntity(int $memberId): MemberEntity
    {
        /** @var null|Member $model */
        $model = $this->repository->findById($memberId);

        if (! $model) {
            throw new BusinessException(ResultCode::NOT_FOUND, "会员不存在: ID={$memberId}");
        }

        return MemberMapper::fromModel($model);
    }

    public function getInfoByOpenId(string $openId): ?Member
    {
        /* @var null|Member $member */
        return $this->repository->findByOpenid($openId);
    }

    /**
     * @return array<string, int>
     */
    public function stats(array $filters): array
    {
        return $this->repository->stats($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        return $this->repository->overview($filters);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array
    {
        return $this->repository->detail($id);
    }

    /**
     * 小程序登录.
     */
    public function miniProgramLogin(string $code, ?string $encryptedData = null, ?string $iv = null, ?string $ip = null, ?string $manualOpenid = null): MemberEntity
    {
        if (! empty($encryptedData) && ! empty($iv)) {
            $payload = $this->miniApp->performSilentLogin($code, $encryptedData, $iv);
        } else {
            $payload = $this->miniApp->silentAuthorize($code);
        }

        $openid = $manualOpenid ?: (string) ($payload['openid'] ?? '');

        if (empty($openid)) {
            throw new \InvalidArgumentException('授权失败');
        }

        $memberEntity = $this->repository->findByOpenid($openid);

        if (! $memberEntity) {
            $memberEntity = MemberMapper::fromMiniProfile($payload);
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip ?? '');
            $model = $this->repository->save($memberEntity);
            $memberEntity->setId($model->id);
        } else {
            $memberEntity->setUnionid($payload['unionid'] ?? $memberEntity->getUnionid());
            $memberEntity->setNickname($payload['nickname'] ?? $memberEntity->getNickname());
            $memberEntity->setAvatar($payload['avatarUrl'] ?? $memberEntity->getAvatar());
            $memberEntity->setGender($payload['gender'] ?? $memberEntity->getGender());
            $memberEntity->setSource('mini_program');
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip ?? '');
            $this->repository->updateEntity($memberEntity);
        }

        return $memberEntity;
    }

    /**
     * 绑定会员手机号.
     *
     * @return array{phoneNumber: string, purePhoneNumber: string, countryCode: null|string}
     */
    public function bindPhoneNumber(MemberEntity $memberEntity, string $code): array
    {
        $payload = $this->miniApp->getPhoneNumber($code);
        $phoneInfo = $payload['phone_info'] ?? $payload;
        $phoneNumber = (string) ($phoneInfo['phoneNumber'] ?? $phoneInfo['purePhoneNumber'] ?? '');

        if (trim($phoneNumber) === '') {
            throw new \InvalidArgumentException('获取手机号失败');
        }

        $memberEntity->setPhone($phoneNumber);
        $this->repository->updateEntity($memberEntity);

        return [
            'phoneNumber' => $phoneNumber,
            'purePhoneNumber' => (string) ($phoneInfo['purePhoneNumber'] ?? $phoneNumber),
            'countryCode' => $phoneInfo['countryCode'] ?? null,
        ];
    }

    private function ensureMemberExists(int $memberId): void
    {
        if (! $this->repository->existsById($memberId)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }
    }

    private function applyTags(int $memberId, array $tagIds): void
    {
        $available = $tagIds === [] ? [] : $this->memberTagRepository
            ->getQuery()
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->toArray();

        $this->repository->syncTags($memberId, $available);
    }
}
