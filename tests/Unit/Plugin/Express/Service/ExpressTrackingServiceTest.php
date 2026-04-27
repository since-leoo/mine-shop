<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Express\Service;

use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Plugin\Express\Exception\TrackingException;
use Plugin\Express\Service\ExpressSettingsResolver;
use Plugin\Express\Service\ExpressTrackingService;

/**
 * @internal
 * @coversNothing
 */
final class ExpressTrackingServiceTest extends TestCase
{
    public function testTrackThrowsWhenPluginIsDisabled(): void
    {
        $resolver = $this->createMock(ExpressSettingsResolver::class);
        $resolver->method('toArray')->willReturn([
            'enabled' => false,
            'default_provider' => 'kuaidi100',
            'customer' => '',
            'key' => '',
            'endpoint' => 'https://poll.kuaidi100.com/poll/query.do',
            'cache_ttl' => 300,
            'timeout' => 5,
        ]);

        $service = new ExpressTrackingService(
            $this->createMock(ClientFactory::class),
            $resolver
        );

        $this->expectException(TrackingException::class);
        $service->track('shunfeng', 'SF123456789');
    }
}
