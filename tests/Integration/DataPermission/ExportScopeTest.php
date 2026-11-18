<?php

declare(strict_types=1);

namespace HyperfTests\Integration\DataPermission;

use App\Infrastructure\Model\Permission\User;
use Hyperf\Context\Context;
use HyperfTests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Plugin\ExportCenter\Service\DtoHydrator;
use Plugin\ExportCenter\Service\ExportDtoResolver;
use Plugin\ExportCenter\Service\ExportService;

final class ExportScopeTest extends DatabaseTestCase
{
    public static function rejectedQueueCases(): array
    {
        return [
            'create rejected' => [false, false],
            'create exception' => [false, true],
            'retry rejected' => [true, false],
            'retry exception' => [true, true],
        ];
    }

    #[DataProvider('rejectedQueueCases')]
    public function testRejectedQueueMarksTaskFailedWithoutExposingOrMaskingOriginalError(bool $retry, bool $throws): void
    {
        $this->migrate('migrations/2025_02_18_000000_create_export_tables.php');
        $resolver = $this->createMock(ExportDtoResolver::class);
        $resolver->method('resolve')->willReturn([]);
        $driver = $this->createMock(\Hyperf\AsyncQueue\Driver\DriverInterface::class);
        $cause = new \RuntimeException('redis://private-host secret queue failure');
        $push = $driver->expects(self::once())->method('push');
        $throws ? $push->willThrowException($cause) : $push->willReturn(false);
        $queue = $this->createMock(\Hyperf\AsyncQueue\Driver\DriverFactory::class);
        $queue->method('get')->with('export')->willReturn($driver);
        $logged = [];
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects(self::exactly(2))->method('error')->willReturnCallback(static function (string $message, array $context) use (&$logged): void {
            $logged[] = $context;
        });
        $this->container->set(\Psr\Log\LoggerInterface::class, $logger);
        $events = $this->createMock(\Psr\EventDispatcher\EventDispatcherInterface::class);
        $notificationError = new \RuntimeException('notification failed');
        $events->method('dispatch')->willReturnCallback(static function (object $event) use ($notificationError): object {
            if ($event instanceof \Plugin\ExportCenter\Event\ExportTaskFailed) {
                throw $notificationError;
            }
            return $event;
        });
        $this->container->set(\Psr\EventDispatcher\EventDispatcherInterface::class, $events);
        $service = new ExportService($resolver, $this->createMock(DtoHydrator::class), $this->createMock(\Psr\SimpleCache\CacheInterface::class), $queue, new \Plugin\ExportCenter\Service\ExportFileService(), $this->createMock(\Plugin\ExportCenter\Service\ExportAccessPolicy::class));
        if ($retry) {
            $this->connection->table('export_tasks')->insert(['id' => 1, 'user_id' => 7, 'status' => 'failed', 'task_name' => 'fixture', 'dto_class' => 'fixture']);
        }
        try {
            $retry ? $service->retryTask(1, 7) : $service->export(7, 'fixture', 'fixture');
            self::fail('Rejected queue must fail the request');
        } catch (\DomainException $error) {
            self::assertSame('导出任务暂时无法提交，请稍后重试', $error->getMessage());
            self::assertSame(\App\Infrastructure\Error\ExportError::QUEUE_SUBMISSION_FAILED->value, $error->getCode());
            $original = $error->getPrevious();
            self::assertInstanceOf(\RuntimeException::class, $original);
            $throws ? self::assertSame($cause, $original) : self::assertSame(\App\Infrastructure\Error\ExportError::QUEUE_REJECTED->message(), $original->getMessage());
            self::assertSame($original, $logged[0]['exception']);
            self::assertSame($original, $logged[1]['original_exception']);
            self::assertSame($notificationError, $logged[1]['exception']);
            $task = $this->connection->table('export_tasks')->first();
            self::assertSame('failed', $task->status);
            self::assertSame(1, $task->retry_count);
            self::assertSame($error->getMessage(), $task->error_message);
        }
    }

    public function testQueueRestoresActorBeforeResolvingProviderAndCleansContextAfterFailure(): void
    {
        (new \Mine\Support\Listener\RegisterBlueprintListener())->process(new \stdClass());
        require_once BASE_PATH . '/migrations/2021_04_12_160526_create_user_table.php';
        (new \CreateUserTable())->up();
        $this->connection->table('user')->insert(['id' => 7, 'username' => 'exporter', 'password' => 'fixture', 'status' => 1]);
        $this->migrate('migrations/2025_02_18_000000_create_export_tables.php');
        $this->connection->table('export_tasks')->insert(['id' => 1, 'user_id' => 7, 'status' => 'pending', 'task_name' => 'fixture', 'dto_class' => 'fixture']);
        $resolver = $this->createMock(ExportDtoResolver::class);
        $resolver->expects(self::once())->method('resolve')->willReturnCallback(static function (): array {
            self::assertSame(7, Context::get('current_user')->id);
            self::assertTrue(Context::get('data_permission_enabled'));
            throw new \RuntimeException('provider fixture failure');
        });
        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $service = new ExportService(
            $resolver,
            $this->createMock(DtoHydrator::class),
            $cache,
            $this->createMock(\Hyperf\AsyncQueue\Driver\DriverFactory::class),
            new \Plugin\ExportCenter\Service\ExportFileService(),
            $this->createMock(\Plugin\ExportCenter\Service\ExportAccessPolicy::class)
        );
        $previous = new User(['id' => 9]);
        Context::set('current_user', $previous);
        Context::set('data_permission_enabled', false);
        try {
            $service->processExportTask(1);
            self::fail('provider failure expected');
        } catch (\RuntimeException $error) {
            self::assertSame('provider fixture failure', $error->getMessage());
            self::assertSame(\App\Infrastructure\Error\ExportError::PROCESSING_FAILED->message(), $this->connection->table('export_tasks')->value('error_message'));
            self::assertSame($previous, Context::get('current_user'));
            self::assertFalse(Context::get('data_permission_enabled'));
            self::assertSame('failed', $this->connection->table('export_tasks')->value('status'));
        } finally {
            Context::destroy('current_user');
            Context::destroy('data_permission_enabled');
        }
    }
}
