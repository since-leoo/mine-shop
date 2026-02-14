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

namespace App\Application\Admin\Organization;

use App\Domain\Organization\Contract\Position\PositionInput;
use App\Domain\Organization\Contract\Position\PositionSetDataPermissionInput;
use App\Domain\Organization\Service\DomainPositionService;
use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Infrastructure\Model\Permission\Position;

final class AppPositionCommandService
{
    public function __construct(private readonly DomainPositionService $positionService) {}

    public function create(PositionInput $input): Position
    {
        return $this->positionService->create($input);
    }

    public function update(int $id, PositionInput $input): bool
    {
        return $this->positionService->update($id, $input);
    }

    public function delete(DeleteInput $input): int
    {
        return $this->positionService->delete($input->getIds());
    }

    public function setDataPermission(PositionSetDataPermissionInput $input): bool
    {
        return $this->positionService->setDataPermissionPolicy(
            $input->getPositionId(),
            $input->getPolicyType(),
            $input->getValue()
        );
    }
}
