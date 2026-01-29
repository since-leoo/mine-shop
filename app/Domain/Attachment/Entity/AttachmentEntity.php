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

namespace App\Domain\Attachment\Entity;

/**
 * 附件实体.
 */
final class AttachmentEntity
{
    private int $id = 0;

    private int $createdBy = 0;

    private string $originName = '';

    private string $storageMode = '';

    private string $objectName = '';

    private string $mimeType = '';

    private string $storagePath = '';

    private string $hash = '';

    private string $suffix = '';

    private int $sizeByte = 0;

    private string $sizeInfo = '';

    private string $url = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy = 0): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getOriginName(): string
    {
        return $this->originName;
    }

    public function setOriginName(string $originName = ''): self
    {
        $this->originName = $originName;
        return $this;
    }

    public function getStorageMode(): string
    {
        return $this->storageMode;
    }

    public function setStorageMode(string $storageMode = ''): self
    {
        $this->storageMode = $storageMode;
        return $this;
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function setObjectName(string $objectName = ''): self
    {
        $this->objectName = $objectName;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType = ''): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath = ''): self
    {
        $this->storagePath = $storagePath;
        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash = ''): self
    {
        $this->hash = $hash;
        return $this;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    public function setSuffix(string $suffix = ''): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function getSizeByte(): int
    {
        return $this->sizeByte;
    }

    public function setSizeByte(int $sizeByte = 0): self
    {
        $this->sizeByte = $sizeByte;
        return $this;
    }

    public function getSizeInfo(): string
    {
        return $this->sizeInfo;
    }

    public function setSizeInfo(string $sizeInfo = ''): self
    {
        $this->sizeInfo = $sizeInfo;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url = ''): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id ?: null,
            'created_by' => $this->createdBy,
            'origin_name' => $this->originName ?: null,
            'storage_mode' => $this->storageMode ?: null,
            'object_name' => $this->objectName ?: null,
            'mime_type' => $this->mimeType ?: null,
            'storage_path' => $this->storagePath ?: null,
            'hash' => $this->hash ?: null,
            'suffix' => $this->suffix ?: null,
            'size_byte' => $this->sizeByte,
            'size_info' => $this->sizeInfo ?: null,
            'url' => $this->url ?: null,
        ], static fn ($v) => $v !== null);
    }

    /**
     * 转换为API响应数组.
     *
     * @return array<string, mixed>
     */
    public function toResponse(): array
    {
        return [
            'id' => $this->id,
            'created_by' => $this->createdBy,
            'origin_name' => $this->originName,
            'storage_mode' => $this->storageMode,
            'object_name' => $this->objectName,
            'mime_type' => $this->mimeType,
            'storage_path' => $this->storagePath,
            'hash' => $this->hash,
            'suffix' => $this->suffix,
            'size_byte' => $this->sizeByte,
            'size_info' => $this->sizeInfo,
            'url' => $this->url,
        ];
    }
}
