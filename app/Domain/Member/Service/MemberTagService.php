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

use App\Domain\Member\Contract\MemberTagInput;
use App\Domain\Member\Repository\MemberTagRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Member\MemberTag;

/**
 * 会员标签服务.
 */
final class MemberTagService extends IService
{
    public function __construct(public readonly MemberTagRepository $repository) {}

    /**
     * 创建会员标签.
     */
    public function create(MemberTagInput $dto): MemberTag
    {
        // 使用 DTO 的 toArray() 方法获取数据
        return $this->repository->create($dto->toArray());
    }

    /**
     * 更新会员标签.
     */
    public function update(MemberTagInput $dto): ?MemberTag
    {
        $tag = $this->repository->findById($dto->getId());
        if (! $tag) {
            return null;
        }

        // 使用 DTO 的 toArray() 方法获取数据
        $this->repository->updateById($dto->getId(), $dto->toArray());

        return $tag->refresh();
    }

    /**
     * 删除会员标签.
     */
    public function delete(int $id): bool
    {
        return $this->repository->deleteById($id) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this->repository->allActive();
    }
}
