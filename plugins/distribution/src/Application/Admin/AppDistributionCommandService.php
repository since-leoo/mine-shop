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

namespace Plugin\Distribution\Application\Admin;

use Hyperf\DbConnection\Db;
use Plugin\Distribution\Domain\Contract\DistributionSettingsInput;
use Plugin\Distribution\Domain\Contract\WithdrawalReviewInput;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Distribution\Domain\Service\DomainWithdrawalService;

final class AppDistributionCommandService
{
    public function __construct(private readonly DomainDistributionService $distribution, private readonly DomainWithdrawalService $withdrawals) {}

    public function settings(DistributionSettingsInput $input, int $operator): array
    {
        return Db::transaction(fn () => $this->distribution->updateSettings($input, $operator));
    }

    public function review(int $id, WithdrawalReviewInput $input, int $operator): array
    {
        return Db::transaction(fn () => $this->withdrawals->review($id, $input, $operator));
    }
}
