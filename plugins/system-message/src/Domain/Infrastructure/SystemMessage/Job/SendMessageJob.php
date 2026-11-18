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

namespace Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Job;

use App\Infrastructure\Error\SystemMessageError;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\NotificationService;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserMessage;
use Psr\Log\LoggerInterface;

class SendMessageJob extends Job
{
    /**
     * 任务执行失败后的重试次数，即最大执行次数为 $maxAttempts+1 次
     */
    protected int $maxAttempts = 3;

    public function __construct(
        public int $messageId,
        public int $userId,
        public string $channel
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $notificationService = $container->get(NotificationService::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            // 获取消息
            $message = Message::query()->find($this->messageId);
            if (! $message) {
                $logger->warning('Message not found for notification job', [
                    'message_id' => $this->messageId,
                    'user_id' => $this->userId,
                    'channel' => $this->channel,
                ]);
                // 外层业务事务可能尚未提交；重试而不是永久丢弃已入队的通知。
                throw SystemMessageError::MESSAGE_NOT_VISIBLE->internal();
            }

            $failure = null;
            $result = Db::transaction(function () use ($message, $notificationService, &$failure): bool {
                // 仅串行同一收件人的重复任务，避免已确认成功的渠道再次投递。
                $receipt = UserMessage::query()->where('message_id', $this->messageId)
                    ->where('user_id', $this->userId)->lockForUpdate()->first();
                if ($receipt === null) {
                    throw SystemMessageError::RECEIPT_NOT_VISIBLE->internal();
                }
                if ($receipt->is_deleted) {
                    return false;
                }
                try {
                    return $notificationService->send($message, $this->userId, $this->channel);
                } catch (\Throwable $error) {
                    // 提交失败投递日志，再交给队列重试。
                    $failure = $error;
                    return false;
                }
            });
            if ($failure !== null) {
                throw $failure;
            }

            $logger->info('Message notification job completed', [
                'message_id' => $this->messageId,
                'user_id' => $this->userId,
                'channel' => $this->channel,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Message notification job failed', [
                'message_id' => $this->messageId,
                'user_id' => $this->userId,
                'channel' => $this->channel,
                'error' => $e->getMessage(),
            ]);

            // 重新抛出异常以触发重试机制
            throw $e;
        }
    }

    /**
     * 任务失败时的处理.
     */
    public function failed(\Throwable $e): void
    {
        $container = ApplicationContext::getContainer();
        $logger = $container->get(LoggerInterface::class);

        $logger->error('Message notification job permanently failed', [
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'channel' => $this->channel,
            'error' => $e->getMessage(),
            'max_attempts' => $this->maxAttempts,
        ]);
    }

    /**
     * 获取任务的唯一标识.
     */
    public function getJobId(): string
    {
        return \sprintf('send_message_%d_%d_%s', $this->messageId, $this->userId, $this->channel);
    }
}
