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

namespace App\Domain\Member\Service;

use App\Domain\Member\Contract\MemberLevelInput;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\MemberLevel;
use App\Interface\Common\ResultCode;

final class MemberLevelService extends IService
{
    public function __construct(public readonly MemberLevelRepository $repository) {}

    /**
     * 创建会员等级.
     */
    public function create(MemberLevelInput $dto): MemberLevel
    {
        // 使用 DTO 的 toArray() 方法获取数据
        return $this->repository->create($dto->toArray());
    }

    /**
     * 更新会员等级.
     */
    public function update(MemberLevelInput $dto): ?MemberLevel
    {
        $level = $this->repository->findById($dto->getId());
        if (! $level) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');
        }

        // 使用 DTO 的 toArray() 方法获取数据
        $this->repository->updateById($dto->getId(), $dto->toArray());

        return $level->refresh();
    }

    /**
     * 删除会员等级.
     */
    public function delete(int $id): bool
    {
        if (! $this->repository->existsById($id)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');
        }

        return $this->repository->deleteById($id) > 0;
    }
}
