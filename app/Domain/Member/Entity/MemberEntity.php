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

namespace App\Domain\Member\Entity;

use App\Domain\Member\Contract\MemberInput;
use App\Domain\Member\Contract\ProfileAuthorizeInput;
use App\Domain\Member\Contract\ProfileUpdateInput;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;

/**
 * 会员聚合根.
 */
final class MemberEntity
{
    private int $id = 0;

    private ?string $openid = null;

    private ?string $unionid = null;

    private ?string $nickname = null;

    private ?string $avatar = null;

    private ?string $gender = null;

    private ?string $phone = null;

    private ?Carbon $birthday = null;

    private ?string $city = null;

    private ?string $province = null;

    private ?string $district = null;

    private ?string $street = null;

    private ?string $regionPath = null;

    private ?string $country = null;

    private ?string $level = null;

    private ?int $levelId = null;

    private ?int $growthValue = null;

    private ?string $status = null;

    private ?string $source = null;

    private ?string $remark = null;

    private ?Carbon $lastLoginAt;

    private string $lastLoginIp = '';

    /** @var int[] */
    private array $tagIds = [];

    private ?MemberWalletEntity $wallet = null;

    /**
     * @var array<string, bool>
     */
    private array $dirtyFields = [];

    /**
     * 创建行为方法：接收 DTO，内部组装设置值.
     */
    public function create(MemberInput $dto): self
    {
        $this->setNickname($dto->getNickname());
        $this->setAvatar($dto->getAvatar());
        $this->setGender($dto->getGender());
        $this->setPhone($dto->getPhone());
        $this->setBirthday($dto->getBirthday());
        $this->setCity($dto->getCity());
        $this->setProvince($dto->getProvince());
        $this->setDistrict($dto->getDistrict());
        $this->setStreet($dto->getStreet());
        $this->setRegionPath($dto->getRegionPath());
        $this->setCountry($dto->getCountry());
        $this->setLevel($dto->getLevel() ?? 'bronze');
        $this->setGrowthValue($dto->getGrowthValue());
        $this->setStatus($dto->getStatus() ?? 'active');
        $this->setSource($dto->getSource() ?? 'admin');
        $this->setRemark($dto->getRemark());
        $this->setTagIds($dto->getTagIds());

        return $this;
    }

    /**
     * 更新行为方法：接收 DTO，内部组装设置值.
     */
    public function update(MemberInput $dto): self
    {
        $dto->getNickname() !== null && $this->setNickname($dto->getNickname());
        $dto->getAvatar() !== null && $this->setAvatar($dto->getAvatar());
        $dto->getGender() !== null && $this->setGender($dto->getGender());
        $dto->getPhone() !== null && $this->setPhone($dto->getPhone());
        $dto->getBirthday() !== null && $this->setBirthday($dto->getBirthday());
        $dto->getCity() !== null && $this->setCity($dto->getCity());
        $dto->getProvince() !== null && $this->setProvince($dto->getProvince());
        $dto->getDistrict() !== null && $this->setDistrict($dto->getDistrict());
        $dto->getStreet() !== null && $this->setStreet($dto->getStreet());
        $dto->getRegionPath() !== null && $this->setRegionPath($dto->getRegionPath());
        $dto->getCountry() !== null && $this->setCountry($dto->getCountry());
        $dto->getLevel() !== null && $this->setLevel($dto->getLevel());
        $dto->getGrowthValue() !== null && $this->setGrowthValue($dto->getGrowthValue());
        $dto->getStatus() !== null && $this->setStatus($dto->getStatus());
        $dto->getSource() !== null && $this->setSource($dto->getSource());
        $dto->getRemark() !== null && $this->setRemark($dto->getRemark());

        return $this;
    }

    /**
     * 更新状态行为方法.
     */
    public function updateStatus(string $status): self
    {
        if (! \in_array($status, ['active', 'inactive', 'banned'], true)) {
            throw new BusinessException(ResultCode::FAIL, '无效的会员状态');
        }

        $this->setStatus($status);
        return $this;
    }

    /**
     * 同步标签行为方法.
     *
     * @param int[] $tagIds
     */
    public function syncTags(array $tagIds): self
    {
        $this->setTagIds($tagIds);
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(?string $openid): void
    {
        $this->openid = $openid;
        $this->markDirty('openid');
    }

    public function getUnionid(): ?string
    {
        return $this->unionid;
    }

    public function setUnionid(?string $unionid): void
    {
        $this->unionid = $unionid;
        $this->markDirty('unionid');
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
        $this->markDirty('nickname');
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
        $this->markDirty('avatar');
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(int|string|null $gender): void
    {
        \is_int($gender) && $gender = match ($gender) {
            1 => 'male',
            2 => 'female',
            default => 'unknown',
        };
        $this->gender = $gender;
        $this->markDirty('gender');
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->markDirty('phone');
    }

    public function getBirthday(): ?Carbon
    {
        return $this->birthday;
    }

    public function setBirthday(?Carbon $birthday): void
    {
        $this->birthday = $birthday;
        $this->markDirty('birthday');
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
        $this->markDirty('city');
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): void
    {
        $this->province = $province;
        $this->markDirty('province');
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): void
    {
        $this->district = $district;
        $this->markDirty('district');
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
        $this->markDirty('street');
    }

    public function getRegionPath(): ?string
    {
        return $this->regionPath;
    }

    public function setRegionPath(?string $regionPath): void
    {
        $this->regionPath = $regionPath;
        $this->markDirty('region_path');
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
        $this->markDirty('country');
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): void
    {
        $this->level = $level;
        $this->markDirty('level');
    }

    public function getLevelId(): ?int
    {
        return $this->levelId;
    }

    public function setLevelId(?int $levelId): void
    {
        $this->levelId = $levelId;
        $this->markDirty('level_id');
    }

    public function getGrowthValue(): ?int
    {
        return $this->growthValue;
    }

    public function setGrowthValue(?int $growthValue): void
    {
        $this->growthValue = $growthValue;
        $this->markDirty('growth_value');
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
        $this->markDirty('status');
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
        $this->markDirty('source');
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
        $this->markDirty('remark');
    }

    public function setLastLoginAt(Carbon $now)
    {
        $this->lastLoginAt = $now;
        $this->markDirty('last_login_at');
    }

    public function getLastLoginAt(): Carbon
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginIp(): string
    {
        return $this->lastLoginIp;
    }

    public function setLastLoginIp(string $ip): void
    {
        $this->lastLoginIp = $ip;
        $this->markDirty('last_login_ip');
    }

    /**
     * @return int[]
     */
    public function getTagIds(): array
    {
        return $this->tagIds;
    }

    /**
     * @param int[] $tagIds
     */
    public function setTagIds(array $tagIds): void
    {
        $this->tagIds = array_values(array_unique(array_map('intval', $tagIds)));
    }

    public function getWallet(): ?MemberWalletEntity
    {
        return $this->wallet;
    }

    public function setWallet(MemberWalletEntity $wallet): void
    {
        $this->wallet = $wallet;
    }

    public function bindPhone(string $phone): self
    {
        if (trim($phone) === '') {
            throw new \DomainException('手机号不能为空');
        }

        $this->setPhone($phone);
        return $this;
    }

    /**
     * 授权头像昵称行为方法.
     */
    public function authorizeProfile(ProfileAuthorizeInput $input): self
    {
        $nickname = $input->getNickname();
        if ($nickname !== null && trim($nickname) !== '') {
            $this->setNickname($nickname);
        }

        $avatarUrl = $input->getAvatarUrl();
        if ($avatarUrl !== null && trim($avatarUrl) !== '') {
            $this->setAvatar($avatarUrl);
        }

        if ($input->getGender() !== null) {
            $this->setGender($input->getGender());
        }

        return $this;
    }

    /**
     * 修改个人资料行为方法.
     */
    public function updateProfile(ProfileUpdateInput $input): self
    {
        $nickname = $input->getNickname();
        if ($nickname !== null && trim($nickname) !== '') {
            $this->setNickname($nickname);
        }

        $avatarUrl = $input->getAvatarUrl();
        if ($avatarUrl !== null && trim($avatarUrl) !== '') {
            $this->setAvatar($avatarUrl);
        }

        if ($input->getGender() !== null) {
            $this->setGender($input->getGender());
        }

        $phone = $input->getPhone();
        if ($phone !== null && trim($phone) !== '') {
            $this->setPhone($phone);
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = [];

        foreach (array_keys($this->dirtyFields) as $field) {
            $data[$field] = match ($field) {
                'openid' => $this->openid,
                'unionid' => $this->unionid,
                'nickname' => $this->nickname,
                'avatar' => $this->avatar,
                'gender' => $this->gender,
                'phone' => $this->phone,
                'birthday' => $this->birthday?->toDateString(),
                'city' => $this->city,
                'province' => $this->province,
                'district' => $this->district,
                'street' => $this->street,
                'region_path' => $this->regionPath,
                'country' => $this->country,
                'level' => $this->level,
                'level_id' => $this->levelId,
                'growth_value' => $this->growthValue,
                'status' => $this->status,
                'source' => $this->source,
                'remark' => $this->remark,
                'last_login_at' => $this->lastLoginAt?->toDateTimeString(),
                'last_login_ip' => $this->lastLoginIp,
                default => null,
            };
        }

        return $data;
    }

    public function clearDirty(): void
    {
        $this->dirtyFields = [];
    }

    private function markDirty(string $field): void
    {
        $this->dirtyFields[$field] = true;
    }
}
