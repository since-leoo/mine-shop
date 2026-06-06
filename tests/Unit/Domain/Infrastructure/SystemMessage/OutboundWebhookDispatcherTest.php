<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Infrastructure\SystemMessage;

use App\Domain\Infrastructure\SystemMessage\Service\OutboundWebhookDispatcher;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 * @coversNothing
 */
final class OutboundWebhookDispatcherTest extends TestCase
{
    public function testDispatchReturnsFalseAndDoesNotCreateClientWhenUrlIsEmpty(): void
    {
        $factory = $this->createMock(ClientFactory::class);
        $factory->expects(self::never())->method('create');

        $dispatcher = new OutboundWebhookDispatcher(
            $this->mallSettings(''),
            $factory,
            $this->createMock(LoggerInterface::class)
        );

        self::assertFalse($dispatcher->dispatch('order.shipped', ['order_no' => 'ORD1001']));
    }

    public function testDispatchPostsEventPayloadToConfiguredWebhook(): void
    {
        $mock = new MockHandler([new Response(204)]);
        $factory = $this->createMock(ClientFactory::class);
        $factory->expects(self::once())->method('create')->willReturn(new Client([
            'handler' => HandlerStack::create($mock),
        ]));

        $dispatcher = new OutboundWebhookDispatcher(
            $this->mallSettings('https://hooks.example/orders'),
            $factory,
            $this->createMock(LoggerInterface::class)
        );

        self::assertTrue($dispatcher->dispatch('order.shipped', ['order_no' => 'ORD1001']));
        $request = $mock->getLastRequest();

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://hooks.example/orders', (string) $request->getUri());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame([
            'event' => 'order.shipped',
            'payload' => ['order_no' => 'ORD1001'],
        ], json_decode((string) $request->getBody(), true));
    }

    public function testDispatchReturnsFalseWhenPostFails(): void
    {
        $mock = new MockHandler([new ConnectException('timeout', new Request('POST', 'https://hooks.example/orders'))]);
        $factory = $this->createMock(ClientFactory::class);
        $factory->expects(self::once())->method('create')->willReturn(new Client([
            'handler' => HandlerStack::create($mock),
        ]));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $dispatcher = new OutboundWebhookDispatcher(
            $this->mallSettings('https://hooks.example/orders'),
            $factory,
            $logger
        );

        self::assertFalse($dispatcher->dispatch('order.cancelled', ['order_no' => 'ORD1001']));
    }

    private function mallSettings(string $webhookUrl): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting(
            'aliyun',
            [],
            ['mail' => true, 'system' => true],
            '',
            '',
            $webhookUrl,
            false
        ));

        return $settings;
    }
}
