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

use App\Domain\Product\Contract\ProductInput;
use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Mapper\ProductMapper;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Product\ValueObject\ProductChangeVo;
use App\Domain\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Interface\Common\ResultCode;
use Hyperf\DbConnection\Annotation\Transactional;

/**
 * 商品领域服务：封装商品相关的核心业务逻辑.
 */
final class DomainProductService extends IService
{
    public function __construct(
        public readonly ProductRepository $repository,
        private readonly DomainMallSettingService $mallSettingService,
    ) {}

    /**
     * 创建商品.
     */
    #[Transactional]
    public function create(ProductInput $input): ProductEntity
    {
        // 1. 获取新实体
        $entity = ProductMapper::getNewEntity();

        // 2. 调用实体的 create 行为方法
        $entity->create($input);

        // 3. 应用系统设置约束
        $this->applyProductSettings($entity);

        // 4. 验证实体完整性
        $entity->ensureCanPersist(true);

        // 5. 同步价格范围
        $entity->syncPriceRange();

        // 6. 持久化
        return $this->repository->save($entity);
    }

    /**
     * 更新商品.
     */
    #[Transactional]
    public function update(ProductInput $input): ProductChangeVo
    {
        // 1. 获取实体
        $entity = $this->getEntity($input->getId());

        // 2. 调用实体的 update 行为方法（返回变更信息）
        $changes = $entity->update($input);

        // 3. 应用系统设置约束
        $this->applyProductSettings($entity);

        // 4. 验证实体完整性
        $entity->ensureCanPersist();

        // 5. 同步价格范围
        $entity->syncPriceRange();

        // 6. 持久化
        $this->repository->update($entity);

        // 7. 返回变更信息
        return $changes;
    }

    /**
     * 删除商品.
     */
    public function delete(int $id): void
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new BusinessException(ResultCode::FAIL, '商品不存在');
        }

        $entity = new ProductEntity();
        $entity->setId($id);

        $this->repository->remove($entity);
    }

    /**
     * 批量更新排序.
     */
    public function updateSort(array $sortData): bool
    {
        foreach ($sortData as $item) {
            if (isset($item['id'], $item['sort'])) {
                $this->repository->updateById((int) $item['id'], ['sort' => (int) $item['sort']]);
            }
        }
        return true;
    }

    /**
     * 获取商品实体.
     *
     * 通过 ID 获取 Model，然后通过 Mapper 转换为 Entity.
     * 用于需要调用实体行为方法的场景.
     */
    public function getEntity(int $id): ProductEntity
    {
        /** @var null|Product $product */
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new BusinessException(ResultCode::FAIL, '商品不存在');
        }

        return ProductMapper::fromModel($product);
    }

    private function applyProductSettings(ProductEntity $entity): void
    {
        $entity->applySettingConstraints(
            $this->mallSettingService->product(),
            $this->mallSettingService->content()
        );
    }
}
