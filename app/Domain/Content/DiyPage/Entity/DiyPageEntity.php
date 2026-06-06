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

namespace App\Domain\Content\DiyPage\Entity;

use App\Domain\Content\DiyPage\Enum\DiyPageStatus;

final class DiyPageEntity
{
    private int $id = 0;

    private string $pageKey = '';

    private string $title = '';

    private string $pageType = DiyPageStatus::TYPE_MINIPROGRAM;

    private ?string $description = null;

    private bool $enabled = false;

    private string $status = DiyPageStatus::PAGE_DISABLED;

    private ?int $publishedVersionId = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function define(string $pageKey, string $title, string $pageType, ?string $description = null): self
    {
        $pageKey = trim($pageKey);
        $title = trim($title);

        if ($pageKey === '') {
            throw new \DomainException('页面键不能为空');
        }

        if ($title === '') {
            throw new \DomainException('页面名称不能为空');
        }

        if (! \in_array($pageType, DiyPageStatus::pageTypes(), true)) {
            throw new \DomainException('页面类型无效');
        }

        $this->pageKey = $pageKey;
        $this->title = $title;
        $this->pageType = $pageType;
        $this->description = $description;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        $this->status = DiyPageStatus::PAGE_DISABLED;

        return $this;
    }

    public function publish(int $versionId): self
    {
        $this->publishedVersionId = $versionId;
        $this->status = DiyPageStatus::PAGE_PUBLISHED;

        return $this;
    }

    public function enable(): self
    {
        if ($this->publishedVersionId === null) {
            throw new \DomainException('请先发布页面后再启用');
        }

        $this->enabled = true;
        $this->status = DiyPageStatus::PAGE_PUBLISHED;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page_key' => $this->pageKey,
            'title' => $this->title,
            'page_type' => $this->pageType,
            'description' => $this->description,
            'is_enabled' => $this->enabled,
            'status' => $this->status,
            'published_version_id' => $this->publishedVersionId,
        ];
    }
}
