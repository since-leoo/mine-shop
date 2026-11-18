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

namespace Plugin\Diy\Domain\Content\DiyPage\Entity;

use App\Infrastructure\Error\DiyError;
use Plugin\Diy\Domain\Content\DiyPage\Enum\DiyPageStatus;

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
            throw DiyError::PAGE_KEY_REQUIRED->exception();
        }

        if ($title === '') {
            throw DiyError::PAGE_NAME_REQUIRED->exception();
        }

        if (! \in_array($pageType, DiyPageStatus::pageTypes(), true)) {
            throw DiyError::PAGE_TYPE_INVALID->exception();
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
            throw DiyError::PUBLISHED_PAGE_REQUIRED_FOR_ENABLE->exception();
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
