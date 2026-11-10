<?php

declare(strict_types=1);

namespace HyperfTests\Integration\PluginExtraction;

use App\Domain\Infrastructure\Plugin\Contract\CommerceOverviewInterface;
use App\Domain\Infrastructure\Plugin\Contract\DiyCatalogDataSourceInterface;
use App\Infrastructure\Plugin\NullAnalyticsQuery;
use App\Infrastructure\Plugin\PluginConfigReader;
use App\Infrastructure\Plugin\NullDiyPageQuery;
use App\Infrastructure\Plugin\NullProductReview;
use App\Infrastructure\Plugin\ReviewEligibility;
use App\Interface\Admin\Dto\Review\ReviewDto;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\Analytics\Domain\Infrastructure\Statistics\Repository\StatisticsRepository;
use Plugin\Analytics\Domain\Infrastructure\Statistics\Service\DomainStatisticsService;
use Plugin\Diy\Application\Api\Content\AppApiDiyPageQueryService;
use Plugin\Diy\Domain\Content\DiyPage\Repository\DiyPageRepository;
use Plugin\Diy\Domain\Content\DiyPage\Repository\DiyPublishRecordRepository;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPageService;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPublishService;
use Plugin\Diy\Infrastructure\Model\Content\DiyPage;
use Plugin\Diy\Infrastructure\Model\Content\DiyPagePublishRecord;
use Plugin\ProductReview\Application\Api\Review\AppApiReviewCommandService;
use Plugin\ProductReview\Application\Api\Review\AppApiReviewQueryService;
use Plugin\ProductReview\Domain\Trade\Review\Api\Command\DomainApiReviewCommandService;
use Plugin\ProductReview\Domain\Trade\Review\Api\Query\DomainApiReviewQueryService;
use Plugin\ProductReview\Domain\Trade\Review\Repository\ReviewRepository;
use Plugin\ProductReview\Infrastructure\Model\Review\Review;
use Plugin\ProductReview\Interface\Adapter\ProductReviewGateway;
use Plugin\ProductReview\Interface\Api\Transformer\ReviewTransformer;

final class ExtractionPersistenceTest extends DatabaseTestCase
{
    public function testFreshPluginSchemasCreateEmptyTablesWithFieldComments(): void
    {
        foreach (['analytics', 'diy', 'product-review'] as $plugin) {
            $this->migratePlugin($plugin);
        }
        foreach (['diy_pages', 'reviews', 'stat_daily_sales'] as $table) {
            self::assertSame(0, $this->connection->table($table)->count());
        }
        $tables = $this->connection->select('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()');
        foreach ($tables as $table) {
            self::assertNotSame('', $table->TABLE_COMMENT, $table->TABLE_NAME);
        }
        $columns = $this->connection->select('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()');
        foreach ($columns as $column) {
            self::assertNotSame('', $column->COLUMN_COMMENT, $column->TABLE_NAME . '.' . $column->COLUMN_NAME);
        }
    }

    public function testAnalyticsReadsExistingStatisticsWhileDisabledFallbackKeepsCoreOverview(): void
    {
        $this->migratePlugin('analytics');
        $this->connection->statement('CREATE TABLE members (id BIGINT PRIMARY KEY, last_login_at DATETIME NULL, deleted_at DATETIME NULL)');
        $this->connection->table('stat_daily_sales')->insert(['date' => '2026-10-10', 'paid_amount' => 12345, 'paid_order_count' => 1, 'order_count' => 1]);
        $commerce = $this->createMock(CommerceOverviewInterface::class);
        $commerce->method('overview')->willReturn(['pending' => ['pending_shipment' => 3]]);
        $service = new DomainStatisticsService(new StatisticsRepository(), new NullProductReview(), $commerce);
        self::assertSame(12345, (int) $service->analysis('2026-10-10', '2026-10-10')['summary']['total_sales']);
        self::assertSame(3, $service->welcome()['pending']['pending_shipment']);
        self::assertFalse($service->welcome()['review']['enabled']);
        $disabled = new NullAnalyticsQuery($commerce, new NullProductReview());
        self::assertSame(3, $disabled->welcome()['pending']['pending_shipment']);
        self::assertFalse($disabled->analysis('2026-10-10', '2026-10-10')['enabled']);
        self::assertSame(1, $this->connection->table('stat_daily_sales')->count());
    }

    public function testDiyPublishedPageAndDisabledFallbackPreserveExistingVersion(): void
    {
        $this->migratePlugin('diy');
        $id = $this->connection->table('diy_pages')->insertGetId(['page_key' => 'home', 'title' => '我的店铺', 'page_type' => 'miniprogram', 'is_enabled' => true, 'status' => 'published']);
        $schema = ['version' => 1, 'page' => ['key' => 'home', 'title' => '我的店铺'], 'components' => []];
        $version = $this->connection->table('diy_page_versions')->insertGetId(['page_id' => $id, 'version_no' => 1, 'status' => 'published', 'schema' => json_encode($schema)]);
        $this->connection->table('diy_pages')->where('id', $id)->update(['published_version_id' => $version]);
        $pages = new DomainDiyPageService(new DiyPageRepository(new DiyPage()));
        $publish = new DomainDiyPublishService(new DiyPublishRecordRepository(new DiyPagePublishRecord()), $pages);
        $catalog = $this->createMock(DiyCatalogDataSourceInterface::class);
        $catalog->expects(self::never())->method('storefrontProducts');
        $query = new AppApiDiyPageQueryService($pages, $publish, new \Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyStorefrontService($catalog));
        self::assertNotNull($query->published('home'));
        self::assertNull((new NullDiyPageQuery())->published('home'));
        self::assertSame(1, $this->connection->table('diy_page_versions')->count());
        self::assertNotNull($query->published('home'));
    }

    public function testDiySeedReinstallDoesNotOverwriteMerchantPublishedContent(): void
    {
        $this->migratePlugin('diy');
        $seed = new \Hyperf\Database\Seeders\Seed($this->container->get(\Hyperf\Database\ConnectionResolverInterface::class), new \Hyperf\Support\Filesystem\Filesystem());
        $command = new \Hyperf\Database\Commands\Seeders\SeedCommand($seed);
        (new \ReflectionProperty($command, 'coroutine'))->setValue($command, false);
        $application = new \Symfony\Component\Console\Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->add($command);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $input = ['command' => 'db:seed', '--path' => substr((new PluginConfigReader())->getSeederPath(BASE_PATH . '/plugins/diy'), strlen(BASE_PATH) + 1), '--force' => true];
        self::assertSame(0, $application->run(new \Symfony\Component\Console\Input\ArrayInput($input), $output), $output->fetch());
        self::assertGreaterThan(0, $this->connection->table('diy_pages')->count());
        self::assertSame(4, $this->connection->table('diy_template_categories')->count());
        self::assertSame(4, $this->connection->table('diy_templates')->count());
        self::assertSame(0, $this->connection->table('diy_templates')->whereIn('page_key', ['category_home', 'member_home'])->count());
        self::assertSame(2, $this->connection->table('diy_pages')->where('page_key', 'category')->count());
        $homeSchema = json_decode($this->connection->table('diy_page_versions')->value('schema'), true);
        self::assertStringNotContainsString('02:18:45', json_encode($homeSchema));
        $this->connection->table('diy_pages')->where('page_key', 'home')->update(['title' => '商家已经修改的首页']);
        $this->connection->table('diy_templates')->update(['description' => '商家模板']);
        $count = $this->connection->table('diy_page_versions')->count();
        self::assertSame(0, $application->run(new \Symfony\Component\Console\Input\ArrayInput($input), $output), $output->fetch());
        self::assertSame('商家已经修改的首页', $this->connection->table('diy_pages')->where('page_key', 'home')->value('title'));
        self::assertSame('商家模板', $this->connection->table('diy_templates')->value('description'));
        self::assertSame($count, $this->connection->table('diy_page_versions')->count());
    }

    public function testReviewCreateModerateQueryAndDisableUseStableContracts(): void
    {
        $this->migratePlugin('product-review');
        $this->connection->statement('CREATE TABLE orders (id BIGINT PRIMARY KEY, member_id BIGINT, status VARCHAR(20), deleted_at DATETIME NULL)');
        $this->connection->statement('CREATE TABLE order_items (id BIGINT PRIMARY KEY, order_id BIGINT, product_id BIGINT, sku_id BIGINT, sku_name VARCHAR(100), product_name VARCHAR(100), product_image VARCHAR(255))');
        $this->connection->statement('CREATE TABLE members (id BIGINT PRIMARY KEY, nickname VARCHAR(100), avatar VARCHAR(255), deleted_at DATETIME NULL)');
        $this->connection->table('orders')->insert(['id' => 1, 'member_id' => 9, 'status' => 'completed']);
        $this->connection->table('order_items')->insert(['id' => 2, 'order_id' => 1, 'product_id' => 3, 'sku_id' => 4, 'sku_name' => '红色']);
        $this->connection->table('members')->insert(['id' => 9, 'nickname' => '买家', 'avatar' => '']);
        $repository = new ReviewRepository(new Review());
        $commands = new AppApiReviewCommandService(new DomainApiReviewCommandService($repository, new ReviewEligibility()));
        $gateway = new ProductReviewGateway($commands, new AppApiReviewQueryService(new DomainApiReviewQueryService($repository)), $repository, new ReviewTransformer());
        $input = new ReviewDto(); $input->orderId = 1; $input->orderItemId = 2; $input->rating = 5; $input->content = '喜欢'; $input->isAnonymous = true;
        $id = $gateway->create(9, $input);
        self::assertGreaterThan(0, $id);
        self::assertSame([2], $gateway->reviewedOrderItemIds([2]));
        self::assertSame(0, $gateway->getProductStats(3)['total']);
        $this->connection->table('reviews')->where('id', $id)->update(['status' => 'approved']);
        $result = $gateway->listByProduct(3, [], 1, 10);
        self::assertSame(1, $result['total']);
        self::assertSame('买***家', $result['list'][0]['nickname']);
        self::assertSame('红色', $result['list'][0]['sku_name']);
        self::assertFalse((new NullProductReview())->enabled());
        self::assertSame(1, $this->connection->table('reviews')->count());
        $this->expectException(\RuntimeException::class);
        $gateway->create(9, $input);
    }

    private function migratePlugin(string $plugin): void
    {
        $path = (new PluginConfigReader())->getMigrationPath(BASE_PATH . '/plugins/' . $plugin);
        self::assertNotNull($path, $plugin . ' must expose its migration directory');
        $files = glob($path . '/*.php') ?: [];
        self::assertNotEmpty($files, $plugin . ' must have migration sources');
        foreach ($files as $file) { (require $file)->up(); }
    }
}
