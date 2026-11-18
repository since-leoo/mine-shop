<?php

declare(strict_types=1);

namespace Plugin\Diy\Application\Admin\Content;

use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPublishService;
use Psr\Log\LoggerInterface;

final class AppDiyScheduledPublishService
{
    public function __construct(private readonly DomainDiyPublishService $publishService, private readonly LoggerInterface $logger) {}

    public function execute(): int
    {
        $previousUser = Context::get('current_user');
        $previousScope = Context::get('data_permission_enabled');
        $published = 0;
        try {
            Context::destroy('current_user');
            Context::set('data_permission_enabled', false);
            foreach ($this->publishService->dueScheduleIds() as $id) {
                try {
                    $published += (int) Db::transaction(function () use ($id): bool {
                        $record = $this->publishService->lockDueSchedule($id);
                        if ($record === null) {
                            return false;
                        }
                        Context::set('current_user', $this->publishService->scheduleOperator($record));
                        Context::set('data_permission_enabled', true);
                        $this->publishService->publishScheduled($record);
                        return true;
                    });
                } catch (\Throwable $error) {
                    Context::destroy('current_user');
                    Context::set('data_permission_enabled', false);
                    $this->logger->error('DIY 定时发布失败', ['record_id' => $id, 'exception' => $error]);
                    $reason = $error instanceof \DomainException || $error instanceof \InvalidArgumentException
                        ? $error->getMessage() : '定时发布失败，请查看服务日志后重新预约';
                    $this->publishService->failSchedule($id, $reason);
                } finally {
                    Context::destroy('current_user');
                    Context::set('data_permission_enabled', false);
                }
            }
        } finally {
            $previousUser === null ? Context::destroy('current_user') : Context::set('current_user', $previousUser);
            $previousScope === null ? Context::destroy('data_permission_enabled') : Context::set('data_permission_enabled', $previousScope);
        }
        return $published;
    }
}
