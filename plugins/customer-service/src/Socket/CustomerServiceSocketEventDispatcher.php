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

namespace Plugin\CustomerService\Socket;

use Plugin\CustomerService\Application\Admin\AppCustomerServiceCommandService;
use Plugin\CustomerService\Application\Api\AppApiCustomerServiceCommandService;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Utils\CustomerServiceSocketPayload;
use Plugin\CustomerService\Service\CustomerServiceConversationNotifier;
use Swoole\WebSocket\Server;

final class CustomerServiceSocketEventDispatcher
{
    public function __construct(
        private readonly AppApiCustomerServiceCommandService $command,
        private readonly AppCustomerServiceCommandService $agentCommand,
        private readonly CustomerServiceSocketResponder $responder,
        private readonly CustomerServiceConversationNotifier $notifier,
    ) {}

    public function dispatch(Server $server, int $fd, string $principalType, int $principalId, int $adminUserId, array $message): void
    {
        if ($principalType === 'agent') {
            $this->dispatchAgent($server, $fd, $principalId, $adminUserId, $message);
            return;
        }
        $event = $message['event'];
        $payload = CustomerServiceSocketPayload::payload($message);
        try {
            if ($event === 'ping') {
                $this->responder->send($server, $fd, 'pong', ['at' => time()]);
                return;
            }
            if ($event === 'conversation:open') {
                $conversation = $this->command->open($principalId, 'miniapp', (string) ($payload['subject'] ?? ''));
                $this->responder->send($server, $fd, 'conversation:created', $conversation->toArray());
                $this->notifier->queued($server, $conversation);
                return;
            }
            if ($event === 'message:send') {
                $type = (string) ($payload['message_type'] ?? 'text');
                $clientId = CustomerServiceSocketPayload::clientMessageId($payload);
                $conversationNo = CustomerServiceSocketPayload::conversationNo($payload);
                $messageData = match ($type) {
                    'text' => $this->command->sendText($principalId, $conversationNo, $clientId, (string) ($payload['text'] ?? '')),
                    'image' => $this->command->sendImage($principalId, $conversationNo, $clientId, (string) ($payload['url'] ?? '')),
                    'product' => $this->command->sendProductCard($principalId, $conversationNo, $clientId, (int) ($payload['product_id'] ?? 0), isset($payload['sku_id']) ? (int) $payload['sku_id'] : null),
                    'faq' => $this->command->sendFaq($principalId, $conversationNo, $clientId, (int) ($payload['faq_id'] ?? 0)),
                    default => throw new \InvalidArgumentException('不支持的消息类型'),
                };
                $this->responder->send($server, $fd, 'message:created', $messageData, ['conversation_no' => $conversationNo, 'client_message_id' => $clientId]);
                $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->first();
                if ($conversation !== null) {
                    $this->notifier->memberMessage($server, $conversation, $messageData);
                }
                return;
            }
            $this->responder->error($server, $fd, '客服事件尚未实现');
        } catch (\Throwable $exception) {
            $this->responder->error($server, $fd, $exception->getMessage());
        }
    }

    private function dispatchAgent(Server $server, int $fd, int $agentId, int $adminUserId, array $message): void
    {
        $event = $message['event'];
        $payload = CustomerServiceSocketPayload::payload($message);
        try {
            if ($event === 'ping') {
                $this->responder->send($server, $fd, 'pong', ['at' => time()]);
                return;
            }
            $agent = CustomerServiceAgent::query()->find($agentId) ?? throw new \RuntimeException('客服坐席不存在');
            $conversationNo = CustomerServiceSocketPayload::conversationNo($payload);
            if ($event === 'agent:accept') {
                $conversation = $this->agentCommand->accept($adminUserId, $agent->display_name, $conversationNo);
                $this->responder->send($server, $fd, 'conversation:accepted', $conversation->toArray());
                $this->notifier->accepted($server, $conversation);
                return;
            }
            if ($event === 'conversation:transfer') {
                $this->agentCommand->transfer($adminUserId, $agent->display_name, $conversationNo, (int) ($payload['agent_id'] ?? 0));
                $targetAgentId = (int) ($payload['agent_id'] ?? 0);
                $this->responder->send($server, $fd, 'conversation:transferred', ['conversation_no' => $conversationNo]);
                $this->notifier->transferred($server, $conversationNo, $targetAgentId);
                return;
            }
            if ($event === 'conversation:close') {
                $this->agentCommand->close($adminUserId, $agent->display_name, $conversationNo);
                $this->responder->send($server, $fd, 'conversation:closed', ['conversation_no' => $conversationNo]);
                $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->first();
                if ($conversation !== null) {
                    $this->notifier->closed($server, $conversation);
                }
                return;
            }
            if ($event === 'message:send') {
                $messageData = $this->agentCommand->sendText($agentId, $conversationNo, (string) ($payload['client_message_id'] ?? ''), (string) ($payload['text'] ?? ''));
                $this->responder->send($server, $fd, 'message:created', $messageData, ['conversation_no' => $conversationNo]);
                $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->first();
                if ($conversation !== null) {
                    $this->notifier->agentMessage($server, $conversation, $messageData);
                }
                return;
            }
            $this->responder->error($server, $fd, '客服事件尚未实现');
        } catch (\Throwable $exception) {
            $this->responder->error($server, $fd, $exception->getMessage());
        }
    }
}
