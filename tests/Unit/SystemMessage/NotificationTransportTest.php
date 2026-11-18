<?php

declare(strict_types=1);

namespace HyperfTests\Unit\SystemMessage;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Hyperf\Config\Config;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\SystemMessage\Infrastructure\Adapter\NotificationTransport;

final class NotificationTransportTest extends TestCase
{
    public function testGatewayRequiresReceiptAndSendsRecipientAuthenticationAndStableDeliveryKey(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(202, [], '{"success":true}')]));
        $stack->push(Middleware::history($history));
        $factory = $this->createMock(ClientFactory::class);
        $factory->method('create')->willReturn(new Client(['handler' => $stack]));
        $transport = new NotificationTransport($this->config(), $factory);
        $transport->gateway('push', 7, ['message_id' => 11, 'title' => '发货']);
        self::assertSame('Bearer fixture-token', $history[0]['request']->getHeaderLine('Authorization'));
        self::assertSame('push:11:7', $history[0]['request']->getHeaderLine('Idempotency-Key'));
        self::assertSame(['channel' => 'push', 'user_id' => 7, 'message' => ['message_id' => 11, 'title' => '发货']], json_decode((string) $history[0]['request']->getBody(), true));
        self::assertSame(5, $history[0]['options']['timeout']);
        self::assertFalse($history[0]['options']['allow_redirects']);
    }

    #[DataProvider('rejectedReceipts')]
    public function testGatewayDoesNotTreatHttpSuccessWithoutPositiveReceiptAsDelivery(int $status, string $body): void
    {
        $factory = $this->createMock(ClientFactory::class);
        $factory->method('create')->willReturn(new Client(['handler' => new MockHandler([new Response($status, [], $body)])]));
        $this->expectException(\RuntimeException::class);
        (new NotificationTransport($this->config(), $factory))->gateway('push', 7, ['message_id' => 11]);
    }

    public static function rejectedReceipts(): array
    {
        return [[500, '{"success":true}'], [200, '{}'], [200, '{"success":false}'], [200, 'not-json']];
    }

    public function testMissingGatewayConfigurationFailsBeforeAnyNetworkCall(): void
    {
        $factory = $this->createMock(ClientFactory::class);
        $factory->expects(self::never())->method('create');
        $this->expectException(\RuntimeException::class);
        (new NotificationTransport(new Config([]), $factory))->gateway('websocket', 7, ['message_id' => 11]);
    }

    public function testEmailUsesConfiguredSenderAndReportsTransportFailure(): void
    {
        $transport = $this->getMockBuilder(NotificationTransport::class)->setConstructorArgs([$this->config(), $this->createMock(ClientFactory::class)])->onlyMethods(['mail'])->getMock();
        $transport->expects(self::once())->method('mail')->with('user@example.test', self::anything(), '<p>Hello</p>', ['MIME-Version' => '1.0', 'Content-Type' => 'text/html; charset=UTF-8', 'From' => 'shop@example.test'])->willReturn(false);
        $this->expectException(\RuntimeException::class);
        $transport->email('user@example.test', '通知', '<p>Hello</p>');
    }

    public function testEmailRejectsHeaderInjectionWithoutSending(): void
    {
        $transport = $this->getMockBuilder(NotificationTransport::class)->setConstructorArgs([$this->config(), $this->createMock(ClientFactory::class)])->onlyMethods(['mail'])->getMock();
        $transport->expects(self::never())->method('mail');
        $this->expectException(\InvalidArgumentException::class);
        $transport->email('user@example.test', "hello\r\nBcc: other@example.test", 'body');
    }

    private function config(): Config
    {
        return new Config(['system_message' => ['email' => ['from' => 'shop@example.test'], 'gateways' => ['push' => ['url' => 'https://gateway.example.test/notifications', 'token' => 'fixture-token']]]]);
    }
}
