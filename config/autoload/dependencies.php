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
use App\Application\Admin\Infrastructure\JwtTokenChecker;
use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;
use App\Domain\Infrastructure\Plugin\Contract\AnalyticsQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOverviewInterface;
use App\Domain\Infrastructure\Plugin\Contract\DiyCatalogDataSourceInterface;
use App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\ProductReviewCommandInterface;
use App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\ReviewEligibilityInterface;
use App\Domain\Infrastructure\Plugin\Contract\ReviewStatisticsInterface;
use App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface;
use App\Domain\Trade\Coupon\Service\CouponServiceAdapter;
use App\Domain\Trade\GroupBuy\Strategy\GroupBuyOrderStrategy;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use App\Domain\Trade\Order\Contract\OrderJobQueueInterface;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Repository\OrderSubmissionRepository;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Domain\Trade\Seckill\Strategy\SeckillOrderStrategy;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TradeWebhookInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Domain\Trade\Shipping\Service\FreightServiceAdapter;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Interface\InterfaceCache;
use App\Infrastructure\Plugin\CommerceOverview;
use App\Infrastructure\Plugin\DiyCatalogDataSource;
use App\Infrastructure\Plugin\InstalledPluginMigratorFactory;
use App\Infrastructure\Plugin\NullAnalyticsQuery;
use App\Infrastructure\Plugin\NullDiyPageQuery;
use App\Infrastructure\Plugin\NullProductReview;
use App\Infrastructure\Plugin\NullSystemNotification;
use App\Infrastructure\Plugin\OptionalCapabilityFactory;
use App\Infrastructure\Plugin\PluginConfigReader;
use App\Infrastructure\Plugin\ReviewEligibility;
use App\Infrastructure\Service\Pay\YsdPayService;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use App\Infrastructure\Service\Trade\NullTradeWebhook;
use App\Infrastructure\Service\Trade\OrderJobQueue;
use App\Infrastructure\Service\Trade\RedisStockReservation;
use Hyperf\Database\Migrations\Migrator;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;
use Mine\Upload\Factory;
use Mine\Upload\UploadInterface;
use Psr\Container\ContainerInterface;
use SinceLeoo\Plugin\Contract\PluginConfigReaderInterface;

return [
    // 插件源码可以独立存放，但标准迁移命令必须发现已安装插件的建表文件。
    PluginConfigReaderInterface::class => PluginConfigReader::class,
    Migrator::class => InstalledPluginMigratorFactory::class,
    UploadInterface::class => Factory::class,
    CheckTokenInterface::class => JwtTokenChecker::class,
    InterfaceCache::class => ICache::class,
    SystemNotificationInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, SystemNotificationInterface::class, NullSystemNotification::class),
    TradeWebhookInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, TradeWebhookInterface::class, NullTradeWebhook::class),
    TransactionManagerInterface::class => HyperfTransactionManager::class,
    PaymentGatewayInterface::class => YsdPayService::class,
    TradeOutboxInterface::class => TradeOutboxRepository::class,
    StockReservationInterface::class => RedisStockReservation::class,
    OrderSubmissionStoreInterface::class => OrderSubmissionRepository::class,
    OrderJobQueueInterface::class => OrderJobQueue::class,
    AnalyticsQueryInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, AnalyticsQueryInterface::class, NullAnalyticsQuery::class),
    DiyPageQueryInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, DiyPageQueryInterface::class, NullDiyPageQuery::class),
    ProductReviewQueryInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, ProductReviewQueryInterface::class, NullProductReview::class),
    ProductReviewCommandInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, ProductReviewCommandInterface::class, NullProductReview::class),
    ReviewStatisticsInterface::class => static fn (ContainerInterface $container) => OptionalCapabilityFactory::resolve($container, ReviewStatisticsInterface::class, NullProductReview::class),
    CommerceOverviewInterface::class => CommerceOverview::class,
    ReviewEligibilityInterface::class => ReviewEligibility::class,
    DiyCatalogDataSourceInterface::class => DiyCatalogDataSource::class,
    ProductSnapshotInterface::class => DomainProductSnapshotService::class,
    CouponServiceInterface::class => CouponServiceAdapter::class,
    FreightServiceInterface::class => FreightServiceAdapter::class,
    OrderTypeStrategyFactory::class => static function (ContainerInterface $container) {
        return new OrderTypeStrategyFactory([
            $container->get(NormalOrderStrategy::class),
            $container->get(SeckillOrderStrategy::class),
            $container->get(GroupBuyOrderStrategy::class),
        ]);
    },
];
