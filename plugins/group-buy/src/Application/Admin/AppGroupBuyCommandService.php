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

namespace Plugin\Since\GroupBuy\Application\Admin;

use Hyperf\DbConnection\Db;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyCreateInput;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyUpdateInput;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyService;

final class AppGroupBuyCommandService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly AppGroupBuyQueryService $queryService,
    ) {}

    public function create(GroupBuyCreateInput $input): bool
    {
        return Db::transaction(fn () => $this->groupBuyService->create($input));
    }

    public function update(GroupBuyUpdateInput $input): bool
    {
        return Db::transaction(fn () => $this->groupBuyService->update($input));
    }

    public function delete(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');
        return Db::transaction(fn () => $this->groupBuyService->delete($id));
    }

    public function toggleStatus(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');
        return Db::transaction(fn () => $this->groupBuyService->toggleStatus($id));
    }
}
