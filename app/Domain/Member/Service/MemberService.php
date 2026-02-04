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

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Repository\MemberTagRepository;
use App\Domain\Member\Trait\MemberMapperTrait;
use Carbon\Carbon;
use Plugin\Wechat\Interfaces\MiniAppInterface;

/**
 * 会员领域服务.
 */
final class MemberService
{
    use MemberMapperTrait;

    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly MemberTagRepository $memberTagRepository,
        private readonly MiniAppInterface $miniApp,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->memberRepository->page($filters, $page, $pageSize);
    }

    /**
     * @return array<string, int>
     */
    public function stats(array $filters): array
    {
        return $this->memberRepository->stats($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        return $this->memberRepository->overview($filters);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array
    {
        return $this->memberRepository->detail($id);
    }

    public function create(MemberEntity $entity): void
    {
        if (! $entity->getSource()) {
            $entity->setSource('admin');
        }

        $member = $this->memberRepository->save($entity);

        $entity->setId($member->id);

        if ($entity->getTagIds() !== []) {
            $this->applyTags($entity->getId(), $entity->getTagIds());
        }
    }

    /**
     * 更新会员档案.
     */
    public function update(MemberEntity $entity): void
    {
        $this->ensureMemberExists($entity->getId());
        $this->memberRepository->updateEntity($entity);
    }

    /**
     * 更新会员状态.
     */
    public function updateStatus(MemberEntity $entity): void
    {
        $memberId = $entity->getId();
        $this->ensureMemberExists($memberId);

        if (! $entity->getStatus()) {
            throw new \InvalidArgumentException('会员状态不能为空');
        }

        $this->memberRepository->updateEntity($entity);
    }

    /**
     * 获取会员信息.
     */
    public function getInfoEntity(int $memberId): MemberEntity
    {
        return $this->memberRepository->findById($memberId);
    }

    /**
     * 同步会员标签.
     */
    public function syncTags(MemberEntity $entity): void
    {
        $memberId = $entity->getId();
        $this->ensureMemberExists($memberId);
        $this->applyTags($memberId, $entity->getTagIds());
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

        $memberEntity = $this->memberRepository->findByOpenid($openid);

        if (! $memberEntity) {
            $memberEntity = self::fromMiniProfile($payload);
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip ?? '');
            $model = $this->memberRepository->save($memberEntity);
            $memberEntity->setId($model->id);
        } else {
            $memberEntity->setUnionid($payload['unionid'] ?? $memberEntity->getUnionid());
            $memberEntity->setNickname($payload['nickname'] ?? $memberEntity->getNickname());
            $memberEntity->setAvatar($payload['avatarUrl'] ?? $memberEntity->getAvatar());
            $memberEntity->setGender($payload['gender'] ?? $memberEntity->getGender());
            $memberEntity->setSource('mini_program');
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip ?? '');
            $this->memberRepository->updateEntity($memberEntity);
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
        $this->memberRepository->updateEntity($memberEntity);

        return [
            'phoneNumber' => $phoneNumber,
            'purePhoneNumber' => (string) ($phoneInfo['purePhoneNumber'] ?? $phoneNumber),
            'countryCode' => $phoneInfo['countryCode'] ?? null,
        ];
    }

    private function ensureMemberExists(int $memberId): void
    {
        if (! $this->memberRepository->existsById($memberId)) {
            throw new \RuntimeException('会员不存在');
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

        $this->memberRepository->syncTags($memberId, $available);
    }
}
