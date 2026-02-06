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

namespace App\Interface\Admin\Dto\Member;

use App\Domain\Member\Contract\MemberInput;
use Carbon\Carbon;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 会员 DTO.
 */
class MemberDto implements MemberInput
{
    public ?int $id = null;

    public ?string $nickname = null;

    public ?string $avatar = null;

    public ?string $gender = 'unknown';

    public ?string $phone = null;

    public ?string $birthday = null;

    public ?string $city = null;

    public ?string $province = null;

    public ?string $district = null;

    public ?string $street = null;

    public ?string $region_path = null;

    public ?string $country = null;

    public ?string $level = null;

    public ?int $growth_value = null;

    public ?string $status = 'active';

    public ?string $source = null;

    public ?string $remark = null;

    /**
     * @var int[]
     */
    public array $tags = [];

    #[Required]
    public int $operator_id = 0;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getBirthday(): ?Carbon
    {
        return $this->birthday ? Carbon::parse($this->birthday) : null;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getRegionPath(): ?string
    {
        return $this->region_path;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function getGrowthValue(): ?int
    {
        return $this->growth_value;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    /**
     * @return int[]
     */
    public function getTagIds(): array
    {
        return $this->tags;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
