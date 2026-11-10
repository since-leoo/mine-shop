<?php

declare(strict_types=1);

namespace HyperfTests\Unit\PluginExtraction;

use App\Domain\Infrastructure\Plugin\Contract\AnalyticsQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOverviewInterface;
use App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface;
use App\Infrastructure\Plugin\NullAnalyticsQuery;
use App\Infrastructure\Plugin\NullDiyPageQuery;
use App\Infrastructure\Plugin\NullProductReview;
use App\Infrastructure\Plugin\OptionalCapabilityFactory;
use App\Interface\Api\Transformer\DiyPageTransformer;
use App\Interface\Api\Transformer\OrderTransformer;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use Hyperf\Collection\Collection;
use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\TestCase;

final class OptionalCapabilitiesTest extends TestCase
{
    public function testDisabledCapabilitiesResolveWithoutPluginClassesAndKeepBasicDashboard(): void
    {
        $overview = $this->createMock(CommerceOverviewInterface::class);
        $overview->method('overview')->willReturn(['pending' => ['pending_shipment' => 7]]);
        $reviews = new NullProductReview();
        $analytics = new NullAnalyticsQuery($overview, $reviews);
        $diy = new NullDiyPageQuery();
        $container = new TestContainer([ConfigInterface::class => new Config([]), NullAnalyticsQuery::class => $analytics, NullDiyPageQuery::class => $diy, NullProductReview::class => $reviews]);
        self::assertSame($analytics, OptionalCapabilityFactory::resolve($container, AnalyticsQueryInterface::class, NullAnalyticsQuery::class));
        self::assertSame(7, $analytics->welcome()['pending']['pending_shipment']);
        self::assertFalse($analytics->welcome()['analytics_enabled']);
        self::assertSame([], $analytics->analysis('2026-01-01', '2026-01-31')['trends']['sales']);
        self::assertSame(['page' => null, 'components' => [], 'publishedAt' => null], (new DiyPageTransformer())->transform(OptionalCapabilityFactory::resolve($container, DiyPageQueryInterface::class, NullDiyPageQuery::class)->published('home')));
        self::assertFalse(OptionalCapabilityFactory::resolve($container, ProductReviewQueryInterface::class, NullProductReview::class)->enabled());
    }

    public function testLoadedProviderSelectsImplementationAndDisableFallsBack(): void
    {
        $enabled = $this->createMock(DiyPageQueryInterface::class);
        $enabled->method('published')->willReturn(['page' => ['title' => '店铺首页']]);
        $config = new Config(['plugin_capabilities' => [DiyPageQueryInterface::class => $enabled::class]]);
        $container = new TestContainer([ConfigInterface::class => $config, $enabled::class => $enabled, NullDiyPageQuery::class => new NullDiyPageQuery()]);
        self::assertSame('店铺首页', OptionalCapabilityFactory::resolve($container, DiyPageQueryInterface::class, NullDiyPageQuery::class)->published('home')['page']['title']);
        $config->set('plugin_capabilities', []);
        self::assertNull(OptionalCapabilityFactory::resolve($container, DiyPageQueryInterface::class, NullDiyPageQuery::class)->published('home'));
    }

    public function testInvalidProviderBindingFailsClearly(): void
    {
        $container = new TestContainer([ConfigInterface::class => new Config(['plugin_capabilities' => [DiyPageQueryInterface::class => \stdClass::class]])]);
        $this->expectException(\LogicException::class);
        OptionalCapabilityFactory::resolve($container, DiyPageQueryInterface::class, NullDiyPageQuery::class);
    }

    public function testCompletedOrderDoesNotOfferReviewsWhenPluginDisabledOrItemsAlreadyReviewed(): void
    {
        $order = (new \ReflectionClass(Order::class))->newInstanceWithoutConstructor();
        $order->setRawAttributes(['status' => 'completed']);
        $item = (object) ['id' => 42];
        $order->setRelation('items', new Collection([$item]));
        $method = new \ReflectionMethod(OrderTransformer::class, 'resolveCompletedButtons');
        $disabled = $method->invoke(new OrderTransformer(new NullProductReview()), $order);
        self::assertSame([9], array_column($disabled, 'type'));
        $reviews = $this->createMock(ProductReviewQueryInterface::class);
        $reviews->method('enabled')->willReturn(true);
        $reviews->expects(self::once())->method('reviewedOrderItemIds')->with([42])->willReturn([42]);
        self::assertSame([9], array_column($method->invoke(new OrderTransformer($reviews), $order), 'type'));
        $unreviewed = $this->createMock(ProductReviewQueryInterface::class);
        $unreviewed->method('enabled')->willReturn(true);
        $unreviewed->method('reviewedOrderItemIds')->willReturn([]);
        self::assertSame([9, 6], array_column($method->invoke(new OrderTransformer($unreviewed), $order), 'type'));
    }

    public function testPluginProvidersExposeOnlyImplementationsOfCoreContracts(): void
    {
        foreach ([new \Plugin\Analytics\ConfigProvider(), new \Plugin\Diy\ConfigProvider(), new \Plugin\ProductReview\ConfigProvider()] as $provider) {
            foreach ($provider()['plugin_capabilities'] as $contract => $implementation) {
                self::assertTrue(is_a($implementation, $contract, true), $implementation . ' must implement ' . $contract);
            }
        }
    }
}
