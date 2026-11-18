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

namespace Plugin\ExportCenter\Service;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Plugin\ExportCenter\Contract\ExportTaskInput;
use Plugin\ExportCenter\Contract\ExportWriterInterface;
use Plugin\ExportCenter\Dto\ExportTaskDto;
use Plugin\ExportCenter\Enum\ExportFormat;
use Plugin\ExportCenter\Enum\ExportStatus;
use Plugin\ExportCenter\Event\ExportTaskCompleted;
use Plugin\ExportCenter\Event\ExportTaskFailed;
use Plugin\ExportCenter\Job\ProcessExportJob;
use Plugin\ExportCenter\Model\ExportDownloadLog;
use Plugin\ExportCenter\Model\ExportTask;
use Plugin\ExportCenter\Writer\PhpSpreadsheetWriter;
use Plugin\ExportCenter\Writer\XlsWriterWriter;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 导出服务
 *
 * 统一入口：外部传入 DTO 类（带 ExportColumn 注解）+ params 数组，
 * 内部自动解析注解、选择最优 Excel 扩展写入。
 * 支持自动分片（超过配置行数拆分多文件并打包 zip）。
 * 文件保存在私有目录，每次下载重新验证当前权限。
 */
final class ExportService
{
    public function __construct(
        private readonly ExportDtoResolver $dtoResolver,
        private readonly DtoHydrator $hydrator,
        private readonly CacheInterface $cache,
        private readonly DriverFactory $queueFactory,
        private readonly ExportFileService $files,
        private readonly ExportAccessPolicy $access,
    ) {}

    /**
     * 业务控制器调用的便捷方法.
     */
    public function export(int $userId, string $taskName, string $dtoClass, array $params = [], string $exportFormat = 'excel'): ExportTask
    {
        return $this->createTask(new ExportTaskDto(
            userId: $userId,
            taskName: $taskName,
            dtoClass: $dtoClass,
            exportFormat: $exportFormat,
            exportParams: $params,
        ));
    }

    /**
     * 创建导出任务并推送队列.
     */
    public function createTask(ExportTaskInput $dto): ExportTask
    {
        $this->dtoResolver->resolve($dto->getDtoClass());

        $task = Db::transaction(static fn () => ExportTask::query()->create([
            'user_id' => $dto->getUserId(),
            'task_name' => $dto->getTaskName(),
            'dto_class' => $dto->getDtoClass(),
            'export_format' => $dto->getExportFormat(),
            'export_params' => $dto->getExportParams(),
            'status' => ExportStatus::PENDING->value,
            'expired_at' => Carbon::now()->addDays((int) config('export.expire_days', 7)),
        ]));

        $this->enqueueTask($task->id);

        return $task;
    }

    /**
     * 处理导出任务（由队列 Job 调用）.
     */
    public function processExportTask(int $taskId): void
    {
        $previousUser = \Hyperf\Context\Context::get('current_user');
        $previousScope = \Hyperf\Context\Context::get('data_permission_enabled');
        try {
            $task = ExportTask::query()->find($taskId);
            $actor = $task ? \App\Infrastructure\Model\Permission\User::find($task->user_id) : null;
            if ($actor === null || $actor->status->isDisable()) {
                throw new \DomainException('导出任务操作者不存在或已停用');
            }
            \Hyperf\Context\Context::set('current_user', $actor);
            \Hyperf\Context\Context::set('data_permission_enabled', true);
            $this->doProcessExportTask($taskId);
        } catch (\Throwable $e) {
            $this->recordTaskFailure($taskId, $e instanceof \DomainException ? $e->getMessage() : '导出处理失败，请联系管理员查看日志', $e);
            throw $e;
        } finally {
            $previousScope === null ? \Hyperf\Context\Context::destroy('data_permission_enabled') : \Hyperf\Context\Context::set('data_permission_enabled', $previousScope);
            $previousUser === null ? \Hyperf\Context\Context::destroy('current_user') : \Hyperf\Context\Context::set('current_user', $previousUser);
        }
    }

    public function updateProgress(int $taskId, int $progress): void
    {
        $this->cache->set("export:progress:{$taskId}", $progress, 3600);

        // 只在关键节点更新数据库，减少写入频率
        if (\in_array($progress, [0, 25, 50, 75, 100], true)) {
            ExportTask::query()->where('id', $taskId)->update(['progress' => $progress]);
        }
    }

    public function markTaskFailed(int $taskId, string $errorMessage): void
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            return;
        }

        $task->update([
            'status' => ExportStatus::FAILED->value,
            'error_message' => $errorMessage,
            'retry_count' => $task->retry_count + 1,
        ]);

        $this->cache->delete("export:progress:{$taskId}");
        event(new ExportTaskFailed($task));
    }

    public function retryTask(int $taskId, int $userId): void
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            throw new \DomainException('导出任务不存在');
        }

        if ($task->user_id !== $userId) {
            throw new \DomainException('无权限重试此任务');
        }

        $maxRetries = (int) config('export.max_retries', 3);
        if ($task->retry_count >= $maxRetries || $task->status !== ExportStatus::FAILED->value) {
            throw new \DomainException('该任务无法重试');
        }

        if ($task->file_path !== null) {
            $this->files->deleteFile($task->file_path);
        }
        $task->update([
            'status' => ExportStatus::PENDING->value,
            'progress' => null,
            'error_message' => null,
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
        ]);

        $this->enqueueTask($taskId);
    }

    public function deleteTask(int $taskId, int $userId): bool
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            return false;
        }

        if ($task->user_id !== $userId) {
            throw new \DomainException('无权限删除此任务');
        }

        // 不允许删除正在处理的任务
        if ($task->status === ExportStatus::PROCESSING->value) {
            throw new \DomainException('正在处理的任务无法删除');
        }

        if ($task->file_path !== null) {
            $this->files->deleteFile($task->file_path);
        }
        return (bool) $task->delete();
    }

    public function getUserTasks(int $userId, array $filters, int $page, int $pageSize): array
    {
        $query = ExportTask::query()
            ->where('user_id', $userId)
            ->when(! empty($filters['status']), static fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['dto_class']), static fn ($q) => $q->where('dto_class', $filters['dto_class']))
            ->when(! empty($filters['start_date']), static fn ($q) => $q->where('created_at', '>=', $filters['start_date']))
            ->when(! empty($filters['end_date']), static fn ($q) => $q->where('created_at', '<=', $filters['end_date']))
            ->orderByDesc('created_at');

        $paginator = $query->paginate(perPage: $pageSize, page: $page);

        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
        ];
    }

    public function getTaskDetail(int $taskId, int $userId): array
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            throw new \DomainException('导出任务不存在');
        }

        if ($task->user_id !== $userId) {
            throw new \DomainException('无权限访问此任务');
        }

        return $task->toArray();
    }

    public function getTaskProgress(int $taskId, int $userId): array
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            throw new \DomainException('导出任务不存在');
        }

        if ($task->user_id !== $userId) {
            throw new \DomainException('无权限访问此任务');
        }

        $cachedProgress = $this->cache->get("export:progress:{$taskId}");

        return [
            'task_id' => $task->id,
            'status' => $task->status,
            'progress' => $cachedProgress !== null ? (int) $cachedProgress : $task->progress,
        ];
    }

    /**
     * 获取已通过当前权限复核的私有文件。
     */
    public function getDownloadFile(int $taskId, int $userId): array
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            throw new \DomainException('导出任务不存在');
        }

        if ($task->user_id !== $userId) {
            throw new \DomainException('无权限下载此文件');
        }

        if ($task->status !== ExportStatus::SUCCESS->value) {
            throw new \DomainException('该任务尚未完成导出');
        }

        if (! $task->file_path) {
            throw new \DomainException('文件不存在或已过期');
        }

        // 检查文件是否已过期
        if ($task->expired_at && Carbon::parse($task->expired_at)->isPast()) {
            throw new \DomainException('文件已过期，请重新导出');
        }

        $actor = \App\Infrastructure\Model\Permission\User::query()->find($userId);
        if ($actor === null) {
            throw new \DomainException('导出任务操作者不存在');
        }
        $path = $this->files->path($task->file_path, $this->access->fingerprint($actor));
        if (! is_file($path)) {
            throw new \DomainException('文件不存在或已过期，请重新导出');
        }

        ExportDownloadLog::query()->create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'ip_address' => ip(),
            'user_agent' => ApplicationContext::getContainer()->get(RequestInterface::class)->getHeaderLine('User-Agent') ?: null,
            'downloaded_at' => Carbon::now(),
        ]);

        return [
            'path' => $path,
            'file_name' => $task->file_name,
        ];
    }

    /**
     * 入队失败必须结束等待状态，保留失败记录以便显式重试。
     */
    private function enqueueTask(int $taskId): void
    {
        try {
            if (! $this->queueFactory->get('export')->push(new ProcessExportJob($taskId))) {
                throw new \RuntimeException('Export queue rejected the task');
            }
        } catch (\Throwable $error) {
            ApplicationContext::getContainer()->get(LoggerInterface::class)->error('导出任务入队失败', [
                'task_id' => $taskId,
                'exception' => $error,
            ]);
            $message = '导出任务暂时无法提交，请稍后重试';
            $this->recordTaskFailure($taskId, $message, $error);
            throw new \DomainException($message, 0, $error);
        }
    }

    private function recordTaskFailure(int $taskId, string $message, \Throwable $originalError): void
    {
        try {
            $this->markTaskFailed($taskId, $message);
        } catch (\Throwable $error) {
            // 通知或状态记录失败不得掩盖原始导出/队列异常。
            ApplicationContext::getContainer()->get(LoggerInterface::class)->error('记录导出失败状态或发送通知失败', [
                'task_id' => $taskId,
                'exception' => $error,
                'original_exception' => $originalError,
            ]);
        }
    }

    /**
     * 实际处理导出任务逻辑.
     */
    private function doProcessExportTask(int $taskId): void
    {
        $task = ExportTask::query()->find($taskId);
        if (! $task) {
            throw new \RuntimeException("导出任务不存在: {$taskId}");
        }

        $status = (string) $task->status;

        if (\in_array($status, [ExportStatus::PROCESSING->value, ExportStatus::SUCCESS->value], true)) {
            return;
        }

        if ($status !== ExportStatus::PENDING->value) {
            throw new \DomainException("只有待处理状态的任务才能开始处理，当前状态: {$status}");
        }

        $affected = ExportTask::query()->where('id', $taskId)
            ->where('status', ExportStatus::PENDING->value)
            ->update([
                'status' => ExportStatus::PROCESSING->value,
                'progress' => 0,
                'started_at' => Carbon::now(),
            ]);

        if ($affected === 0) {
            return;
        }

        $task->refresh();

        // 解析 DTO 注解 & 获取数据
        $meta = $this->dtoResolver->resolve($task->dto_class);
        $dataProvider = $meta['sheet']['dataProvider'] ?? null;
        if (! $dataProvider || ! \is_array($dataProvider) || \count($dataProvider) !== 2) {
            throw new \RuntimeException("DTO {$task->dto_class} 未配置 dataProvider");
        }
        $actor = \App\Infrastructure\Model\Permission\User::query()->find($task->user_id);
        if ($actor === null) {
            throw new \DomainException('导出任务操作者不存在');
        }
        $fingerprint = $this->access->fingerprint($actor);
        [$serviceClass, $method] = $dataProvider;
        $service = ApplicationContext::getContainer()->get($serviceClass);
        $rawData = $service->{$method}($task->export_params);

        // 通过 DtoHydrator 将原始数据自动映射为导出行
        $columns = $meta['columns'];
        $hydrator = $this->hydrator;
        $data = (static function () use ($rawData, $columns, $hydrator) {
            foreach ($rawData as $row) {
                yield $hydrator->hydrate($columns, $row);
            }
        })();

        $tempRoot = (string) config('export.temp_path', BASE_PATH . '/storage/exports/temp');
        $baseName = $task->id . '_' . bin2hex(random_bytes(16));
        $tempPath = $tempRoot . '/' . $baseName;
        if (! mkdir($tempPath, 0700, true)) {
            throw new \RuntimeException('无法创建导出临时目录');
        }
        $privateKey = null;
        try {
            $format = ExportFormat::from($task->export_format);
            $tempFile = $tempPath . '/' . $baseName . '.' . $format->extension();
            $progressCallback = fn (int $progress) => $this->updateProgress($taskId, $progress);
            $maxRowsPerFile = (int) config('export.max_rows_per_file', 50000);
            $writer = $this->resolveWriter();
            $generatedFiles = ($format === ExportFormat::EXCEL)
                ? $writer->writeExcel($tempFile, $meta, $data, $progressCallback, $maxRowsPerFile)
                : $writer->writeCsv($tempFile, $meta, $data, $progressCallback, $maxRowsPerFile);
            $finalFile = $this->packageFiles($generatedFiles, $tempPath, $baseName, $task->task_name, $format);
            $actor->refresh();
            if (! hash_equals($fingerprint, $this->access->fingerprint($actor))) {
                throw new \DomainException('导出期间数据权限已变更，请重新导出');
            }
            $fileName = $task->task_name . '.' . pathinfo($finalFile, PATHINFO_EXTENSION);
            $fileSize = filesize($finalFile);
            $privateKey = $this->files->store($finalFile, $fingerprint);
            $task->update([
                'status' => ExportStatus::SUCCESS->value,
                'progress' => 100,
                'file_path' => $privateKey,
                'file_size' => $fileSize ?: 0,
                'file_name' => $fileName,
                'completed_at' => Carbon::now(),
            ]);
        } catch (\Throwable $error) {
            if ($privateKey !== null) {
                $this->files->deleteFile($privateKey);
            }
            throw $error;
        } finally {
            $this->cleanupTempFiles(glob($tempPath . '/*') ?: []);
            rmdir($tempPath);
        }

        $this->cache->delete("export:progress:{$taskId}");
        event(new ExportTaskCompleted($task));
    }

    /**
     * 多文件打包为 zip，单文件直接返回.
     */
    private function packageFiles(array $files, string $tempPath, string $baseName, string $taskName, ExportFormat $format): string
    {
        if ($files === []) {
            throw new \RuntimeException('导出未生成文件');
        }
        if (\count($files) === 1) {
            return $files[0];
        }

        $zipPath = $tempPath . '/' . $baseName . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('无法创建 zip 文件');
        }

        foreach ($files as $i => $file) {
            $entryName = $taskName . '_' . ($i + 1) . '.' . $format->extension();
            $zip->addFile($file, $entryName);
        }

        $zip->close();
        return $zipPath;
    }

    /**
     * 清理临时文件.
     */
    private function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    private function resolveWriter(): ExportWriterInterface
    {
        if (\extension_loaded('xlswriter')) {
            return new XlsWriterWriter();
        }

        return new PhpSpreadsheetWriter();
    }
}
