<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Socket;

use Plugin\CustomerService\Application\Admin\AppCustomerServiceCommandService;
use Plugin\CustomerService\Application\Api\AppApiCustomerServiceCommandService;
use Plugin\CustomerService\Domain\Contract\CustomerServiceAuthorizationInterface;
use Plugin\CustomerService\Infrastructure\Utils\CustomerServiceSocketPayload;
use Swoole\WebSocket\Server;

/** Commands own notifications through their outbox; the transport sends only acknowledgements. */
final class CustomerServiceSocketEventDispatcher
{
    public function __construct(
        private readonly AppApiCustomerServiceCommandService $command,
        private readonly AppCustomerServiceCommandService $agentCommand,
        private readonly CustomerServiceSocketResponder $responder,
        private readonly CustomerServiceAuthorizationInterface $authorization,
    ) {}

    public function dispatch(Server $server, int $fd, string $principalType, int $principalId, int $adminUserId, array $message): void
    {
        $payload = CustomerServiceSocketPayload::payload($message);
        $event = (string) ($message['event'] ?? '');
        $context = [
            'request_id' => $message['request_id'] ?? null,
            'conversation_no' => $payload['conversation_no'] ?? null,
            'client_message_id' => $payload['client_message_id'] ?? null,
        ];
        try {
            if ($principalType === 'agent') {
                $agent = $this->authorization->authorizeAgentAction($adminUserId, $principalId, $event);
                $no = CustomerServiceSocketPayload::conversationNo($payload);
                switch ($event) {
                    case 'agent:accept':
                        $conversation = $this->agentCommand->accept($adminUserId, $agent['display_name'], $no);
                        $this->responder->send($server, $fd, 'conversation:accepted', $conversation->toArray(), $context);
                        return;
                    case 'conversation:transfer':
                        $this->agentCommand->transfer($adminUserId, $agent['display_name'], $no, (int) ($payload['agent_id'] ?? 0));
                        $this->responder->send($server, $fd, 'conversation:transferred', ['conversation_no' => $no], $context);
                        return;
                    case 'conversation:close':
                        $this->agentCommand->close($adminUserId, $agent['display_name'], $no);
                        $this->responder->send($server, $fd, 'conversation:closed', ['conversation_no' => $no], $context);
                        return;
                    case 'message:send':
                        $data = $this->agentCommand->sendText($principalId, $no, CustomerServiceSocketPayload::clientMessageId($payload), (string) ($payload['text'] ?? ''));
                        $this->responder->send($server, $fd, 'message:created', $data, $context);
                        return;
                }
            }
            if ($principalType !== 'member' || $principalId <= 0) {
                throw new \DomainException('客服连接身份无效');
            }
            if ($event === 'conversation:open') {
                $conversation = $this->command->open($principalId, 'miniapp', (string) ($payload['subject'] ?? ''), (int) ($payload['protocol_version'] ?? 1), isset($payload['category_id']) ? (int) $payload['category_id'] : null);
                $this->responder->send($server, $fd, 'conversation:created', $conversation->toArray(), $context);
                return;
            }
            $no = CustomerServiceSocketPayload::conversationNo($payload);
            if ($event === 'conversation:request-human') {
                $this->command->requestHuman($principalId, $no);
                $this->responder->send($server, $fd, 'conversation:updated', ['conversation_no' => $no], $context);
                return;
            }
            if ($event === 'conversation:classify') {
                $this->command->classify($principalId, $no, (int) ($payload['category_id'] ?? 0));
                $this->responder->send($server, $fd, 'conversation:updated', ['conversation_no' => $no], $context);
                return;
            }
            if ($event === 'conversation:close') {
                $this->command->close($principalId, $no);
                $this->responder->send($server, $fd, 'conversation:closed', ['conversation_no' => $no], $context);
                return;
            }
            if ($event === 'message:send') {
                $clientId = CustomerServiceSocketPayload::clientMessageId($payload);
                $data = match ((string) ($payload['message_type'] ?? 'text')) {
                    'text' => $this->command->sendText($principalId, $no, $clientId, (string) ($payload['text'] ?? '')),
                    'image' => $this->command->sendImage($principalId, $no, $clientId, (string) ($payload['url'] ?? '')),
                    'product' => $this->command->sendProductCard($principalId, $no, $clientId, (int) ($payload['product_id'] ?? 0), isset($payload['sku_id']) ? (int) $payload['sku_id'] : null),
                    'faq' => $this->command->sendFaq($principalId, $no, $clientId, (int) ($payload['faq_id'] ?? 0)),
                    default => throw new \InvalidArgumentException('不支持的消息类型'),
                };
                $this->responder->send($server, $fd, 'message:created', $data, $context);
                return;
            }
            throw new \InvalidArgumentException('不支持的客服操作');
        } catch (\DomainException|\InvalidArgumentException $exception) {
            $this->responder->error($server, $fd, $exception->getMessage(), $context);
        } catch (\Throwable) {
            $this->responder->error($server, $fd, '客服操作失败，请稍后重试', $context);
        }
    }
}
