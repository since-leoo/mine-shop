<?php

declare(strict_types=1);

namespace HyperfTests\Unit\CustomerServiceTransport;

use Hyperf\Config\Config;
use Hyperf\Redis\Redis;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerService\Application\Admin\AppCustomerServiceCommandService;
use Plugin\CustomerService\Application\Api\AppApiCustomerServiceCommandService;
use Plugin\CustomerService\Application\Socket\CustomerServiceSocketTicketService;
use Plugin\CustomerService\Domain\Contract\CustomerServiceAuthorizationInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServiceTicketStoreInterface;
use Plugin\CustomerService\Domain\ValueObject\SocketActionPolicy;
use Plugin\CustomerService\Service\CustomerServiceRealtimeService;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;
use Plugin\CustomerService\Socket\CustomerServiceConnectionTable;
use Plugin\CustomerService\Socket\CustomerServiceSocketEventDispatcher;
use Plugin\CustomerService\Socket\CustomerServiceSocketProtocol;
use Plugin\CustomerService\Socket\CustomerServiceSocketResponder;
use Swoole\WebSocket\Server;

final class TransportTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testReadPermissionCannotAuthorizeMutatingActions(): void
    {
        self::assertSame('customer-service:conversation:read', SocketActionPolicy::permission('read'));
        foreach (['agent:accept', 'message:send', 'conversation:transfer', 'conversation:close'] as $action) {
            self::assertNotSame(SocketActionPolicy::permission('read'), SocketActionPolicy::permission($action));
        }
    }

    public function testUnknownActionIsRejected(): void
    {
        $this->expectException(\DomainException::class);
        SocketActionPolicy::permission('admin:execute');
    }

    public function testUnauthorizedSocketCannotReachApplicationCommand(): void
    {
        $member = $this->createMock(AppApiCustomerServiceCommandService::class);
        $agent = $this->createMock(AppCustomerServiceCommandService::class);
        $agent->expects(self::never())->method('accept');
        $authorization = $this->createMock(CustomerServiceAuthorizationInterface::class);
        $authorization->expects(self::once())->method('authorizeAgentAction')->with(7, 3, 'agent:accept')->willThrowException(new \DomainException('无权限'));
        $responder = $this->createMock(CustomerServiceSocketResponder::class);
        $responder->expects(self::once())->method('error')->with(self::anything(), 11, '无权限', self::anything());
        $dispatcher = new CustomerServiceSocketEventDispatcher($member, $agent, $responder, $authorization);
        $dispatcher->dispatch($this->createMock(Server::class), 11, 'agent', 3, 7, ['event' => 'agent:accept', 'payload' => ['conversation_no' => 'CS1']]);
    }

    public function testAgentSendUsesSameApplicationCommandAndAcknowledgesMessage(): void
    {
        $agent = $this->createMock(AppCustomerServiceCommandService::class);
        $agent->expects(self::once())->method('sendText')->with(3, 'CS1', 'client-1', '您好')->willReturn(['id' => 9]);
        $authorization = $this->createMock(CustomerServiceAuthorizationInterface::class);
        $authorization->method('authorizeAgentAction')->willReturn(['id' => 3, 'admin_user_id' => 7, 'display_name' => '客服']);
        $responder = $this->createMock(CustomerServiceSocketResponder::class);
        $responder->expects(self::once())->method('send')->with(self::anything(), 11, 'message:created', ['id' => 9], self::callback(fn (array $context) => $context['client_message_id'] === 'client-1'));
        $dispatcher = new CustomerServiceSocketEventDispatcher($this->createMock(AppApiCustomerServiceCommandService::class), $agent, $responder, $authorization);
        $dispatcher->dispatch($this->createMock(Server::class), 11, 'agent', 3, 7, ['event' => 'message:send', 'payload' => ['conversation_no' => 'CS1', 'client_message_id' => 'client-1', 'text' => '您好']]);
    }

    public function testProtocolRejectsInvalidAndOversizedFramesAndPreservesCorrelation(): void
    {
        $protocol = new CustomerServiceSocketProtocol();
        self::assertNull($protocol->decode('{broken'));
        self::assertNull($protocol->decode(json_encode(['event' => 'message:send', 'payload' => 'invalid'])));
        self::assertNull($protocol->decode(str_repeat(' ', 65537)));
        $decoded = json_decode($protocol->encode('message:created', ['id' => 4], ['event_id' => 'outbox-4', 'client_message_id' => 'c4', 'conversation_no' => 'CS4']), true);
        self::assertSame('outbox-4', $decoded['event_id']);
        self::assertSame('c4', $decoded['client_message_id']);
        self::assertSame('CS4', $decoded['conversation_no']);
    }

    public function testTicketIsConsumedOnceAndDisabledServiceRejectsIssuing(): void
    {
        $store = new class implements CustomerServiceTicketStoreInterface {
            public array $items = [];
            public function put(string $ticket, array $principal, int $expiresIn): void { $this->items[$ticket] = $principal; }
            public function consume(string $ticket): ?array { $result = $this->items[$ticket] ?? null; unset($this->items[$ticket]); return $result; }
        };
        $settings = $this->createMock(CustomerServiceSettingsResolver::class);
        $settings->method('toArray')->willReturnOnConsecutiveCalls(['enabled' => true], ['enabled' => false]);
        $service = new CustomerServiceSocketTicketService($store, $settings);
        $ticket = $service->issueMemberTicket(42);
        self::assertSame(60, $ticket['expires_in']);
        self::assertSame(42, $service->consume($ticket['ticket'])['principal_id']);
        self::assertNull($service->consume($ticket['ticket']));
        $this->expectException(\DomainException::class);
        $service->issueMemberTicket(42);
    }

    public function testQueueBroadcastContainsOnlyInvalidationAndTargetedMessageRetainsContext(): void
    {
        $redis = \Mockery::mock(Redis::class);
        $sent = [];
        $redis->shouldReceive('publish')->twice()->andReturnUsing(function ($channel, $data) use (&$sent) { $sent[] = json_decode($data, true); return 1; });
        $service = new CustomerServiceRealtimeService($redis, new Config([]), $this->createMock(CustomerServiceConnectionTable::class), $this->createMock(CustomerServiceSocketResponder::class), $this->createMock(CustomerServiceAuthorizationInterface::class));
        $service->publish('conversation:queued', ['member_id' => 42, 'subject' => 'private'], 'agent', null, ['conversation_no' => 'CS42']);
        $service->publish('message:created', ['id' => 4], 'member', 42, ['conversation_no' => 'CS42', 'event_id' => 'event4']);
        self::assertSame(['refresh' => true], $sent[0]['payload']);
        self::assertArrayNotHasKey('conversation_no', $sent[0]['context']);
        self::assertSame(42, $sent[1]['principal_id']);
        self::assertSame('event4', $sent[1]['context']['event_id']);
    }

    public function testMemberBroadcastIsRejected(): void
    {
        $service = new CustomerServiceRealtimeService(\Mockery::mock(Redis::class), new Config([]), $this->createMock(CustomerServiceConnectionTable::class), $this->createMock(CustomerServiceSocketResponder::class), $this->createMock(CustomerServiceAuthorizationInterface::class));
        $this->expectException(\InvalidArgumentException::class);
        $service->publish('message:created', ['id' => 4], 'member');
    }

    public function testSubscriberDeliversOnlyToAuthorizedTargetConnection(): void
    {
        $connections = $this->createMock(CustomerServiceConnectionTable::class);
        $connections->method('all')->willReturn([
            '11' => ['principal_type' => 'member', 'principal_id' => 42, 'admin_user_id' => 0],
            '12' => ['principal_type' => 'member', 'principal_id' => 43, 'admin_user_id' => 0],
            '13' => ['principal_type' => 'agent', 'principal_id' => 42, 'admin_user_id' => 9],
        ]);
        $responder = $this->createMock(CustomerServiceSocketResponder::class);
        $responder->expects(self::once())->method('send')->with(self::anything(), 11, 'message:created', ['id' => 4], ['event_id' => 'e4']);
        $service = new CustomerServiceRealtimeService(\Mockery::mock(Redis::class), new Config([]), $connections, $responder, $this->createMock(CustomerServiceAuthorizationInterface::class));
        $service->deliver($this->createMock(Server::class), json_encode(['event' => 'message:created', 'payload' => ['id' => 4], 'principal_type' => 'member', 'principal_id' => 42, 'context' => ['event_id' => 'e4']]));
        $service->deliver($this->createMock(Server::class), json_encode(['event' => 'message:created', 'payload' => ['id' => 4], 'principal_type' => 'member', 'principal_id' => 42, 'context' => ['event_id' => 'e4']]));
    }
}
