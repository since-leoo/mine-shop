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

namespace App\Domain\Member\Trait;

use App\Domain\Member\Entity\MemberEntity;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;

trait MemberMapperTrait
{
    public static function mapper(Member $member): MemberEntity
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
        $entity->clearDirty();

        return $entity;
    }

    protected static function fromMiniProfile(array $profile): MemberEntity
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
