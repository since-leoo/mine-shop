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

    private ?string $country = null;

    private ?string $level = null;

    private ?int $levelId = null;

    private ?int $growthValue = null;

    private ?string $status = null;

    private ?string $source = null;

    private ?string $remark = null;

    /** @var int[] */
    private array $tagIds = [];

    private ?MemberWalletEntity $wallet = null;

    /**
     * @var array<string, bool>
     */
    private array $dirtyFields = [];

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
        $this->markDirty('openid', $openid);
    }

    public function getUnionid(): ?string
    {
        return $this->unionid;
    }

    public function setUnionid(?string $unionid): void
    {
        $this->unionid = $unionid;
        $this->markDirty('unionid', $unionid);
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
        $this->markDirty('nickname', $nickname);
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
        $this->markDirty('avatar', $avatar);
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
        $this->markDirty('gender', $gender);
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->markDirty('phone', $phone);
    }

    public function getBirthday(): ?Carbon
    {
        return $this->birthday;
    }

    public function setBirthday(?Carbon $birthday): void
    {
        $this->birthday = $birthday;
        $this->markDirty('birthday', $birthday?->toDateString());
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
        $this->markDirty('city', $city);
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): void
    {
        $this->province = $province;
        $this->markDirty('province', $province);
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
        $this->markDirty('country', $country);
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): void
    {
        $this->level = $level;
        $this->markDirty('level', $level);
    }

    public function getLevelId(): ?int
    {
        return $this->levelId;
    }

    public function setLevelId(?int $levelId): void
    {
        $this->levelId = $levelId;
        $this->markDirty('level_id', $levelId);
    }

    public function getGrowthValue(): ?int
    {
        return $this->growthValue;
    }

    public function setGrowthValue(?int $growthValue): void
    {
        $this->growthValue = $growthValue;
        $this->markDirty('growth_value', $growthValue);
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
        $this->markDirty('status', $status);
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
        $this->markDirty('source', $source);
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
        $this->markDirty('remark', $remark);
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
                'country' => $this->country,
                'level' => $this->level,
                'level_id' => $this->levelId,
                'growth_value' => $this->growthValue,
                'status' => $this->status,
                'source' => $this->source,
                'remark' => $this->remark,
                default => null,
            };
        }

        return $data;
    }

    public function clearDirty(): void
    {
        $this->dirtyFields = [];
    }

    private function markDirty(string $field, mixed $value = null, bool $force = false): void
    {
        if ($force || \func_num_args() === 1) {
            $this->dirtyFields[$field] = true;
            return;
        }

        if ($value === null) {
            return;
        }

        if (\is_string($value) && trim($value) === '') {
            return;
        }

        if (\is_array($value) && $value === []) {
            return;
        }

        $this->dirtyFields[$field] = true;
    }
}
