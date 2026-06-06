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

namespace HyperfTests\Unit\Application\Admin\Content;

use App\Application\Admin\Content\AppDiySelectorQueryService;
use App\Domain\Content\DiyPage\Repository\DiySelectorRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AppDiySelectorQueryServiceTest extends TestCase
{
    public function testProductsUseActiveStatusAndPagination(): void
    {
        $repository = new FakeDiySelectorRepository();
        $repository->productsResult = [
            'list' => [
                [
                    'id' => 1,
                    'name' => '测试商品',
                    'main_image' => '/product.png',
                    'min_price' => 9900,
                    'max_price' => 12900,
                    'status' => 'active',
                ],
            ],
            'total' => 1,
        ];
        $service = new AppDiySelectorQueryService($repository);

        $result = $service->products(['keyword' => '测试'], 2, 8);

        self::assertSame('active', $repository->lastProductsParams['status']);
        self::assertSame('测试', $repository->lastProductsParams['keyword']);
        self::assertSame(2, $repository->lastProductsPage);
        self::assertSame(8, $repository->lastProductsPageSize);
        self::assertSame(1, $result['list'][0]['id']);
        self::assertSame('测试商品', $result['list'][0]['name']);
    }

    public function testCategoriesOnlyReturnEnabledTreeOptions(): void
    {
        $repository = new FakeDiySelectorRepository();
        $repository->categoriesResult = [
            ['id' => 10, 'parent_id' => 0, 'name' => '食品', 'level' => 1],
        ];
        $service = new AppDiySelectorQueryService($repository);

        $result = $service->categories(['keyword' => '食']);

        self::assertSame('active', $repository->lastCategoriesParams['status']);
        self::assertSame('食', $repository->lastCategoriesParams['keyword']);
        self::assertSame(10, $result[0]['id']);
        self::assertSame('食品', $result[0]['name']);
    }

    public function testMarketingSelectorsUseEnabledActivities(): void
    {
        $repository = new FakeDiySelectorRepository();
        $repository->couponsResult = ['list' => [['id' => 3, 'name' => '满减券']], 'total' => 1];
        $repository->seckillsResult = ['list' => [['id' => 4, 'title' => '秒杀专场']], 'total' => 1];
        $repository->groupBuysResult = ['list' => [['id' => 5, 'title' => '拼团活动']], 'total' => 1];
        $service = new AppDiySelectorQueryService($repository);

        $coupons = $service->coupons(['keyword' => '满'], 1, 10);
        $seckills = $service->seckills(['keyword' => '秒'], 1, 10);
        $groupBuys = $service->groupBuys(['keyword' => '拼'], 1, 10);

        self::assertSame('active', $repository->lastCouponsParams['status']);
        self::assertTrue($repository->lastSeckillsParams['is_enabled']);
        self::assertTrue(\in_array($repository->lastSeckillsParams['status'], ['active', 'pending'], true));
        self::assertTrue($repository->lastGroupBuysParams['is_enabled']);
        self::assertTrue(\in_array($repository->lastGroupBuysParams['status'], ['active', 'pending'], true));
        self::assertSame('满减券', $coupons['list'][0]['name']);
        self::assertSame('秒杀专场', $seckills['list'][0]['title']);
        self::assertSame('拼团活动', $groupBuys['list'][0]['title']);
    }
}

final class FakeDiySelectorRepository extends DiySelectorRepository
{
    public array $productsResult = ['list' => [], 'total' => 0];

    public array $categoriesResult = [];

    public array $couponsResult = ['list' => [], 'total' => 0];

    public array $seckillsResult = ['list' => [], 'total' => 0];

    public array $groupBuysResult = ['list' => [], 'total' => 0];

    public array $lastProductsParams = [];

    public ?int $lastProductsPage = null;

    public ?int $lastProductsPageSize = null;

    public array $lastCategoriesParams = [];

    public array $lastCouponsParams = [];

    public array $lastSeckillsParams = [];

    public array $lastGroupBuysParams = [];

    public function products(array $params, int $page, int $pageSize): array
    {
        $this->lastProductsParams = $params;
        $this->lastProductsPage = $page;
        $this->lastProductsPageSize = $pageSize;

        return $this->productsResult;
    }

    public function categories(array $params): array
    {
        $this->lastCategoriesParams = $params;

        return $this->categoriesResult;
    }

    public function coupons(array $params, int $page, int $pageSize): array
    {
        $this->lastCouponsParams = $params;

        return $this->couponsResult;
    }

    public function seckills(array $params, int $page, int $pageSize): array
    {
        $this->lastSeckillsParams = $params;

        return $this->seckillsResult;
    }

    public function groupBuys(array $params, int $page, int $pageSize): array
    {
        $this->lastGroupBuysParams = $params;

        return $this->groupBuysResult;
    }
}
