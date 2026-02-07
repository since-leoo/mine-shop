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

namespace App\Domain\Infrastructure\Attachment\Trait;

use App\Domain\Infrastructure\Attachment\Entity\AttachmentEntity;
use App\Infrastructure\Model\Attachment\Attachment;

trait AttachmentMapperTrait
{
    public static function mapper(Attachment $attachment): AttachmentEntity
    {
        $entity = new AttachmentEntity();
        $entity->setId((int) $attachment->id);
        $entity->setCreatedBy((int) $attachment->created_by);
        $entity->setOriginName((string) $attachment->origin_name);
        $entity->setStorageMode((string) $attachment->storage_mode);
        $entity->setObjectName((string) $attachment->object_name);
        $entity->setMimeType((string) $attachment->mime_type);
        $entity->setStoragePath((string) $attachment->storage_path);
        $entity->setHash((string) $attachment->hash);
        $entity->setSuffix((string) $attachment->suffix);
        $entity->setSizeByte((int) $attachment->size_byte);
        $entity->setSizeInfo((string) $attachment->size_info);
        $entity->setUrl((string) $attachment->url);

        return $entity;
    }
}
