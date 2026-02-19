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

namespace App\Domain\Member\Mapper;

use App\Domain\Member\Contract\MemberInput;
use App\Domain\Member\Entity\MemberEntity;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;

/**
 * 会员 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
final class MemberMapper
{
    /**
     * 从 DTO 创建新实体.
     *
     * @param MemberInput $dto 会员输入 DTO
     * @return MemberEntity 会员实体
     */
    public static function fromDto(MemberInput $dto): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取新实体.
     *
     * @deprecated 使用 fromDto 代替
     */
    public static function getNewEntity(): MemberEntity
    {
        return new MemberEntity();
    }

    /**
     * 从持久化模型重建实体.
     *
     * @param Member $member 数据库模型
     * @return MemberEntity 会员实体
     */
    public static function fromModel(Member $member): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->setId($member->id);
        $entity->setOpenid($member->openid);
        $entity->setUnionid($member->unionid);
        $entity->setNickname($member->nickname);
        $entity->setAvatar($member->avatar);
        $entity->setGender($member->gender);
        $entity->setPhone($member->phone);
        if ($member->birthday instanceof Carbon) {
            $entity->setBirthday($member->birthday);
        }
        $entity->setCity($member->city);
        $entity->setProvince($member->province);
        $entity->setDistrict($member->district);
        $entity->setStreet($member->street);
        $entity->setRegionPath($member->region_path);
        $entity->setCountry($member->country);
        $entity->setLevel($member->level);
        $entity->setLevelId($member->level_id);
        $entity->setGrowthValue($member->growth_value);
        $entity->setStatus($member->status);
        $entity->setSource($member->source);
        $entity->setRemark($member->remark);
        $entity->setInviteCode($member->invite_code);
        $entity->setReferrerId($member->referrer_id);
        $entity->clearDirty();

        return $entity;
    }

    /**
     * 从微信小程序用户信息创建实体.
     *
     * @param array<string, mixed> $profile 微信用户信息
     * @return MemberEntity 会员实体
     */
    public static function fromMiniProfile(array $profile): MemberEntity
    {
        $openid = (string) ($profile['openid'] ?? '');
        if (trim($openid) === '') {
            throw new \InvalidArgumentException('微信登录返回的 openid 为空');
        }

        $entity = new MemberEntity();
        $entity->setOpenid($openid);
        $entity->setUnionid($profile['unionid'] ?? null);
        $entity->setNickname($profile['nickname'] ?? '微信用户');
        $entity->setAvatar($profile['avatar'] ?? null);
        $entity->setGender($profile['gender'] ?? 'unknown');
        $entity->setSource('mini_program');
        $entity->setStatus('active');

        return $entity;
    }
}
