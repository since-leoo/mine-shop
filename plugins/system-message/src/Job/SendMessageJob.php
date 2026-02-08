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

namespace Plugin\Since\SystemMessage\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Service\NotificationService;
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
            $message = Message::find($this->messageId);
            if (! $message) {
                $logger->warning('Message not found for notification job', [
                    'message_id' => $this->messageId,
                    'user_id' => $this->userId,
                    'channel' => $this->channel,
                ]);
                return;
            }

            // 发送通知
            $result = $notificationService->send($message, $this->userId, $this->channel);

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

        // 可以在这里添加失败后的处理逻辑，比如：
        // 1. 发送管理员通知
        // 2. 记录到失败队列
        // 3. 尝试其他渠道发送
    }

    /**
     * 获取任务的唯一标识.
     */
    public function getJobId(): string
    {
        return \sprintf('send_message_%d_%d_%s', $this->messageId, $this->userId, $this->channel);
    }
}
