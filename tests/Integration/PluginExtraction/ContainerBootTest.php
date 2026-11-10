<?php

declare(strict_types=1);

namespace HyperfTests\Integration\PluginExtraction;

use App\Domain\Infrastructure\Plugin\Contract\AnalyticsQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface;
use App\Domain\Trade\Shared\Contract\TradeWebhookInterface;
use App\Infrastructure\Plugin\NullAnalyticsQuery;
use App\Infrastructure\Plugin\NullDiyPageQuery;
use App\Infrastructure\Plugin\NullProductReview;
use App\Infrastructure\Service\Trade\NullTradeWebhook;
use PHPUnit\Framework\TestCase;

final class ContainerBootTest extends TestCase
{
    public function testRealContainerLoadsEnabledPluginsAndDisabledFallbacks(): void
    {
        foreach (['enabled', 'disabled'] as $mode) {
            // Each child performs a real ClassLoader scan with private config/cache and no .env.
            $process = proc_open([PHP_BINARY, BASE_PATH . '/tests/Support/container-smoke.php', $mode], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $errors . $output);
            $services = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            // Neither the invalid private database nor WeChat configuration is needed to construct H5 member endpoints.
            self::assertSame(\App\Interface\Api\Controller\V1\Member\MemberController::class, $services[\App\Interface\Api\Controller\V1\Member\MemberController::class]);
            foreach ([
                AnalyticsQueryInterface::class => NullAnalyticsQuery::class,
                DiyPageQueryInterface::class => NullDiyPageQuery::class,
                ProductReviewQueryInterface::class => NullProductReview::class,
                TradeWebhookInterface::class => NullTradeWebhook::class,
                \App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface::class => \App\Infrastructure\Plugin\NullSystemNotification::class,
            ] as $contract => $fallback) {
                if ($mode === 'disabled') { self::assertSame($fallback, $services[$contract]); }
                else { self::assertStringStartsWith('Plugin\\', $services[$contract]); }
            }
            self::assertSame($mode === 'enabled', $services['analytics_autoloadable']);
        }
    }
}
