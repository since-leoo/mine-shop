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

namespace App\Application\Member\Assembler;

use App\Domain\Member\Entity\MemberEntity;
use Carbon\Carbon;

/**
 * 会员装配器.
 */
final class MemberAssembler
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->setNickname((string) $payload['nickname']);
        $entity->setAvatar($payload['avatar'] ?? null);
        $entity->setGender($payload['gender'] ?? 'unknown');
        $entity->setPhone($payload['phone'] ?? null);
        $entity->setCity($payload['city'] ?? null);
        $entity->setProvince($payload['province'] ?? null);
        $entity->setDistrict($payload['district'] ?? null);
        $entity->setStreet($payload['street'] ?? null);
        $entity->setRegionPath($payload['region_path'] ?? null);
        $entity->setCountry($payload['country'] ?? null);
        $entity->setLevel($payload['level'] ?? 'bronze');
        $growthValue = $payload['growth_value'] ?? null;
        $entity->setGrowthValue($growthValue !== null ? (int) $growthValue : null);
        $entity->setStatus($payload['status'] ?? 'active');
        $entity->setSource($payload['source'] ?? 'admin');
        $entity->setRemark($payload['remark'] ?? null);
        $entity->setTagIds(\is_array($payload['tags'] ?? null) ? $payload['tags'] : []);

        $birthday = $payload['birthday'] ?? null;
        $entity->setBirthday($birthday ? Carbon::parse((string) $birthday) : null);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->setId($id);
        $entity->setNickname($payload['nickname'] ?? null);
        $entity->setAvatar($payload['avatar'] ?? null);
        $entity->setGender($payload['gender'] ?? null);
        $entity->setPhone($payload['phone'] ?? null);
        $entity->setCity($payload['city'] ?? null);
        $entity->setProvince($payload['province'] ?? null);
        $entity->setDistrict($payload['district'] ?? null);
        $entity->setStreet($payload['street'] ?? null);
        $entity->setRegionPath($payload['region_path'] ?? null);
        $entity->setCountry($payload['country'] ?? null);
        $entity->setLevel($payload['level'] ?? null);
        $growthValue = $payload['growth_value'] ?? null;
        $entity->setGrowthValue($growthValue !== null ? (int) $growthValue : null);
        $entity->setStatus($payload['status'] ?? null);
        $entity->setSource($payload['source'] ?? null);
        $entity->setRemark($payload['remark'] ?? null);

        $birthday = $payload['birthday'] ?? null;
        $entity->setBirthday($birthday ? Carbon::parse((string) $birthday) : null);

        return $entity;
    }

    public static function toStatusEntity(int $id, string $status): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->setId($id);
        $entity->setStatus($status);

        return $entity;
    }

    /**
     * @param int[] $tags
     */
    public static function toTagEntity(int $id, array $tags): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->setId($id);
        $entity->setTagIds($tags);

        return $entity;
    }
}
