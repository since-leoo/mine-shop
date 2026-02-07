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

namespace App\Domain\Product\Service;

use App\Domain\Product\Contract\BrandInput;
use App\Domain\Product\Repository\BrandRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\BusinessException;
use App\Infrastructure\Model\Product\Brand;
use Mine\Support\ResultCode;

/**
 * 品牌领域服务：封装品牌相关的核心业务逻辑.
 */
final class DomainBrandService extends IService
{
    public function __construct(public readonly BrandRepository $repository) {}

    /**
     * 获取品牌选项.
     */
    public function getOptions(): array
    {
        return $this->repository->getOptions();
    }

    /**
     * 创建品牌.
     */
    public function create(BrandInput $input): Brand
    {
        $data = $input->toArray();

        // 如果没有指定排序，使用下一个排序值
        if (! isset($data['sort']) || $data['sort'] === 0) {
            $data['sort'] = Brand::getNextSort();
        }

        return $this->repository->create($data);
    }

    /**
     * 更新品牌.
     */
    public function update(BrandInput $input): bool
    {
        $id = $input->getId();
        $brand = $this->repository->findById($id);

        if (! $brand) {
            throw new BusinessException(ResultCode::FAIL, '品牌不存在');
        }

        return $this->repository->updateById($id, $input->toArray());
    }

    /**
     * 删除品牌.
     */
    public function delete(int $id): bool
    {
        /** @var null|Brand $brand */
        $brand = $this->repository->findById($id);

        if (! $brand) {
            throw new BusinessException(ResultCode::FAIL, '品牌不存在');
        }

        if (! $brand->canDelete()) {
            throw new BusinessException(ResultCode::FAIL, '该品牌下还有商品，无法删除');
        }

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 批量更新排序.
     */
    public function updateSort(array $sortData): bool
    {
        $sanitized = [];
        foreach ($sortData as $item) {
            if (! isset($item['id'], $item['sort'])) {
                continue;
            }
            $sanitized[] = [
                'id' => (int) $item['id'],
                'sort' => (int) $item['sort'],
            ];
        }

        return $sanitized === [] || $this->repository->updateSort($sanitized);
    }
}
