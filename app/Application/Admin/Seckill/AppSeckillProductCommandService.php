<?php

declare(strict_types=1);

namespace App\Application\Admin\Seckill;

use Hyperf\DbConnection\Db;
use Hyperf\DTO\Mapper;
use App\Domain\Trade\Seckill\Contract\SeckillProductInput;
use App\Domain\Trade\Seckill\Service\DomainSeckillProductService;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Interface\Admin\Dto\Seckill\SeckillProductDto;

final class AppSeckillProductCommandService
{
    public function __construct(private readonly DomainSeckillProductService $productService, private readonly AppSeckillProductQueryService $queryService) {}

    public function create(SeckillProductInput $dto): SeckillProduct
    {
        return Db::transaction(fn () => $this->productService->create($dto));
    }

    public function update(SeckillProductInput $dto): bool
    {
        if (! $this->queryService->find($dto->getId())) {
            throw new \RuntimeException('商品不存在');
        } return Db::transaction(fn () => $this->productService->update($dto));
    }

    public function delete(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('商品不存在');
        } return Db::transaction(fn () => $this->productService->delete($id));
    }

    public function batchCreate(int $activityId, int $sessionId, array $products): array
    {
        $inputs = array_map(static function (array $productData) use ($activityId, $sessionId) {
            return Mapper::map(array_merge($productData, ['activity_id' => $activityId, 'session_id' => $sessionId]), new SeckillProductDto());
        }, $products);
        return Db::transaction(fn () => $this->productService->batchCreate($inputs));
    }

    public function toggleStatus(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('商品不存在');
        } return Db::transaction(fn () => $this->productService->toggleStatus($id));
    }
}
