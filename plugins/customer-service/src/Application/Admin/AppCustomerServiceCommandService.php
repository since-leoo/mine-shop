<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application\Admin;

use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Application\CustomerServiceOutbox;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Domain\Service\DomainAgentService;
use Plugin\CustomerService\Domain\Service\DomainConversationService;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;

final class AppCustomerServiceCommandService
{
    public function __construct(private readonly DomainAgentService $domain, private readonly CustomerServiceConversationRepository $repository, private readonly CustomerServiceOutbox $outbox, private readonly DomainConversationService $conversations, private readonly \Plugin\CustomerService\Domain\Service\DomainFaqService $faqs) {}

    public function agent(int $adminUserId, string $name, ?string $avatar = null): CustomerServiceAgent { return $this->domain->agent($adminUserId, $name, $avatar); }

    public function updateAgentStatus(int $adminUserId, string $name, string $status, ?int $requestedAgentId = null): CustomerServiceAgent
    {
        return Db::transaction(fn () => $this->domain->updateStatus($adminUserId, $name, $status, $requestedAgentId));
    }

    public function accept(int $adminUserId, string $name, string $conversationNo): CustomerServiceConversation
    {
        return Db::transaction(function () use ($adminUserId, $name, $conversationNo) {
            $conversation = $this->domain->accept($adminUserId, $name, $conversationNo);
            $this->outbox->conversationChanged($conversation, 'conversation:accepted');
            return $conversation;
        });
    }

    public function transfer(int $adminUserId, string $name, string $conversationNo, int $targetAgentId): void
    {
        Db::transaction(function () use ($adminUserId, $name, $conversationNo, $targetAgentId) { $this->outbox->conversationChanged($this->domain->transfer($adminUserId, $name, $conversationNo, $targetAgentId), 'conversation:transferred'); });
    }

    public function close(int $adminUserId, string $name, string $conversationNo): void
    {
        Db::transaction(function () use ($adminUserId, $name, $conversationNo) { $this->outbox->conversationChanged($this->domain->close($adminUserId, $name, $conversationNo), 'conversation:closed'); });
    }

    public function sendText(int $agentId, string $conversationNo, string $clientMessageId, string $text): array
    {
        return Db::transaction(function () use ($agentId, $conversationNo, $clientMessageId, $text) {
            $message = $this->domain->sendText($agentId, $conversationNo, $clientMessageId, $text);
            if ($message->wasRecentlyCreated) { $this->outbox->messageCreated($this->repository->lock($conversationNo), $message->toArray()); }
            return $message->toArray();
        });
    }

    public function configureAgent(int $agentId, array $input): array
    {
        return Db::transaction(fn () => $this->domain->configure($agentId, $input)->toArray());
    }

    public function takeover(int $adminUserId, string $name, string $no): CustomerServiceConversation
    {
        return Db::transaction(function () use ($adminUserId, $name, $no) {
            $conversation = $this->repository->lock($no);
            $this->conversations->prepareTakeover($conversation);
            $conversation = $this->domain->accept($adminUserId, $name, $no);
            $this->outbox->conversationChanged($conversation, 'conversation:accepted');
            return $conversation;
        });
    }

    public function classify(string $no, int $categoryId): array
    {
        return Db::transaction(function () use ($no, $categoryId) {
            $conversation = $this->repository->lock($no);
            $this->conversations->classify($conversation, $categoryId, true);
            $this->outbox->conversationChanged($conversation);
            return $conversation->toArray();
        });
    }

    public function saveFaq(?int $id, array $payload): CustomerServiceFaq
    {
        return Db::transaction(fn () => $this->faqs->save($id, $payload));
    }

    public function deleteFaq(int $id): void
    {
        Db::transaction(fn () => $this->faqs->delete($id));
    }
}
