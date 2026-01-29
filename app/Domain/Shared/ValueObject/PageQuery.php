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

namespace App\Domain\Shared\ValueObject;

/**
 * 通用分页查询值对象.
 */
final class PageQuery
{
    /**
     * @var array<string, mixed>
     */
    private array $filters = [];

    private int $page = 1;

    private int $pageSize = 15;

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page = 1): self
    {
        $this->page = $page;
        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize = 15): self
    {
        $this->pageSize = $pageSize;
        return $this;
    }
}
