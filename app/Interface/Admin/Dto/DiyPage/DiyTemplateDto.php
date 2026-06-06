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

namespace App\Interface\Admin\Dto\DiyPage;

use App\Domain\Content\DiyPage\Contract\DiyTemplateInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use Hyperf\DTO\Annotation\Validation\Required;

final class DiyTemplateDto implements DiyTemplateInput
{
    #[Required]
    public int $category_id = 0;

    #[Required]
    public string $name = '';

    #[Required]
    public string $page_key = 'home';

    #[Required]
    public string $page_type = DiyPageStatus::TYPE_ALL;

    public ?string $cover = null;

    public ?string $description = null;

    #[Required]
    public array $schema = [];

    public int $sort = 0;

    public bool $is_enabled = true;

    public function getCategoryId(): int
    {
        return $this->category_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPageKey(): string
    {
        return $this->page_key;
    }

    public function getPageType(): string
    {
        return $this->page_type;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }
}
