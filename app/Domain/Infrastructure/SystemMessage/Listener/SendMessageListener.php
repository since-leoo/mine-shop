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

namespace App\Domain\Infrastructure\SystemMessage\Listener;

use App\Domain\Infrastructure\SystemMessage\Event\SendMessageEvent;
use App\Domain\Infrastructure\SystemMessage\Event\TemplateMessageEvent;
use App\Domain\Infrastructure\SystemMessage\Job\ProcessMessageEventJob;
use App\Domain\Infrastructure\SystemMessage\Service\MessageService;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 发送消息事件监听器.
 *
 * 注意：使用懒加载获取依赖以避免循环依赖
 */
#[Listener]
class SendMessageListener implements ListenerInterface
{
    private ?MessageService $messageService = null;

    private ?DriverFactory $queueDriverFactory = null;

    public function __construct() {}

    public function listen(): array
    {
        return [
            SendMessageEvent::class,
            TemplateMessageEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof SendMessageEvent) {
            $this->handleSendMessageEvent($event);
        } elseif ($event instanceof TemplateMessageEvent) {
            $this->handleTemplateMessageEvent($event);
        }
    }

    /**
     * 处理 SendMessageEvent.
     */
    protected function handleSendMessageEvent(SendMessageEvent $event): void
    {
        try {
            // 如果使用队列
            if ($event->useQueue) {
                $this->pushToQueue(
                    $event->toMessageData(),
                    $event->sendImmediately,
                    $event->queueDelay,
                    $event->queueName
                );
                $event->success = true;

                logger()->info('SendMessageEvent queued', [
                    'title' => $event->title,
                    'type' => $event->type->value,
                    'delay' => $event->queueDelay,
                    'queue' => $event->queueName ?? 'default',
                ]);
                return;
            }

            // 同步处理
            $message = $this->getMessageService()->create($event->toMessageData());
            $event->messageId = $message->id;

            if ($event->sendImmediately) {
                $this->getMessageService()->send($message->id);
            }

            $event->success = true;

            logger()->info('SendMessageEvent processed', [
                'message_id' => $message->id,
                'title' => $event->title,
                'type' => $event->type->value,
                'send_immediately' => $event->sendImmediately,
            ]);
        } catch (\Throwable $e) {
            $event->success = false;
            $event->error = $e->getMessage();

            logger()->error('SendMessageEvent failed', [
                'title' => $event->title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 处理 TemplateMessageEvent.
     */
    protected function handleTemplateMessageEvent(TemplateMessageEvent $event): void
    {
        try {
            // 如果使用队列
            if ($event->useQueue) {
                $this->pushToQueue(
                    $event->toMessageData(),
                    $event->shouldSendImmediately(),
                    $event->queueDelay,
                    $event->queueName
                );
                $event->success = true;

                logger()->info('TemplateMessageEvent queued', [
                    'template_class' => \get_class($event->template),
                    'title' => $event->template->getTitle(),
                    'delay' => $event->queueDelay,
                    'queue' => $event->queueName ?? 'default',
                ]);
                return;
            }

            // 同步处理
            $message = $this->getMessageService()->create($event->toMessageData());
            $event->messageId = $message->id;

            if ($event->shouldSendImmediately()) {
                $this->getMessageService()->send($message->id);
            }

            $event->success = true;

            logger()->info('TemplateMessageEvent processed', [
                'message_id' => $message->id,
                'template_class' => \get_class($event->template),
                'title' => $event->template->getTitle(),
                'send_immediately' => $event->shouldSendImmediately(),
            ]);
        } catch (\Throwable $e) {
            $event->success = false;
            $event->error = $e->getMessage();

            logger()->error('TemplateMessageEvent failed', [
                'template_class' => \get_class($event->template),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 推送到队列.
     */
    protected function pushToQueue(
        array $messageData,
        bool $sendImmediately,
        int $delay = 0,
        ?string $queueName = null
    ): void {
        $job = new ProcessMessageEventJob($messageData, $sendImmediately);

        // 获取队列驱动
        $driverName = $queueName ?? 'default';
        try {
            $driver = $this->getQueueDriverFactory()->get($driverName);
        } catch (\Throwable $e) {
            // 如果指定的队列不存在，使用默认队列
            $driver = $this->getQueueDriverFactory()->get('default');
        }

        // 推送任务
        $driver->push($job, $delay);
    }

    /**
     * 懒加载获取 MessageService.
     */
    private function getMessageService(): MessageService
    {
        if ($this->messageService === null) {
            $this->messageService = ApplicationContext::getContainer()->get(MessageService::class);
        }
        return $this->messageService;
    }

    /**
     * 懒加载获取 DriverFactory.
     */
    private function getQueueDriverFactory(): DriverFactory
    {
        if ($this->queueDriverFactory === null) {
            $this->queueDriverFactory = ApplicationContext::getContainer()->get(DriverFactory::class);
        }
        return $this->queueDriverFactory;
    }
}
