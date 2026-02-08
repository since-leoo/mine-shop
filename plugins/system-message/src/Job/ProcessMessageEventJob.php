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
use Plugin\Since\SystemMessage\Service\MessageService;

/**
 * 处理消息事件的队列任务
 */
class ProcessMessageEventJob extends Job
{
    /**
     * 最大尝试次数.
     */
    public int $maxAttempts = 3;

    /**
     * @param array $messageData 消息数据
     * @param bool $sendImmediately 是否立即发送
     */
    public function __construct(
        protected array $messageData,
        protected bool $sendImmediately = true
    ) {}

    public function handle(): void
    {
        try {
            $messageService = ApplicationContext::getContainer()->get(MessageService::class);

            // 创建消息
            $message = $messageService->create($this->messageData);

            // 如果需要立即发送
            if ($this->sendImmediately) {
                $messageService->send($message->id);
            }

            system_message_logger()->info('ProcessMessageEventJob completed', [
                'message_id' => $message->id,
                'title' => $this->messageData['title'] ?? '',
                'send_immediately' => $this->sendImmediately,
            ]);
        } catch (\Throwable $e) {
            system_message_logger()->error('ProcessMessageEventJob failed', [
                'data' => $this->messageData,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
