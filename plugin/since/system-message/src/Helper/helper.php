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
use Hyperf\Context\ApplicationContext;
use Plugin\Since\SystemMessage\Contract\MessageTemplateInterface;
use Plugin\Since\SystemMessage\Event\TemplateMessageEvent;
use Plugin\Since\SystemMessage\Facade\SystemMessage;
use Psr\Log\LoggerInterface;

if (! function_exists('system_message_logger')) {
    /**
     * 获取系统消息插件专用的 Logger 实例.
     */
    function system_message_logger(): LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerInterface::class);
    }
}

if (! function_exists('send_template_message')) {
    /**
     * 通过模板发送消息.
     *
     * @param MessageTemplateInterface $template 消息模板实例
     * @param bool $useQueue 是否使用队列异步发送
     * @param int $queueDelay 队列延迟秒数
     * @param null|string $queueName 指定队列名称
     */
    function send_template_message(
        MessageTemplateInterface $template,
        bool $useQueue = false,
        int $queueDelay = 0,
        ?string $queueName = null
    ): TemplateMessageEvent {
        return SystemMessage::sendTemplate($template, $useQueue, $queueDelay, $queueName);
    }
}
