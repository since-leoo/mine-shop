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

namespace Plugin\Distribution\Application\Api;

use Hyperf\DbConnection\Db;
use Plugin\Distribution\Domain\Contract\ShareInput;
use Plugin\Distribution\Domain\Contract\WithdrawalInput;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Distribution\Domain\Service\DomainShareService;
use Plugin\Distribution\Domain\Service\DomainWithdrawalService;

final class AppApiDistributionCommandService
{
    public function __construct(private readonly DomainDistributionService $distribution, private readonly DomainWithdrawalService $withdrawals, private readonly DomainShareService $shares) {}

    public function bindNewMember(int $id, ?string $code, ?string $token): array
    {
        return Db::transaction(fn () => $this->distribution->bindNewMember($id, $code, $token));
    }

    public function withdraw(int $id, WithdrawalInput $input): array
    {
        return Db::transaction(fn () => $this->withdrawals->apply($id, $input));
    }

    public function share(int $id, ShareInput $input): array
    {
        return $this->shares->create($id, $input);
    }
}
