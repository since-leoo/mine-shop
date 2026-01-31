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
}
