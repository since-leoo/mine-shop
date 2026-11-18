<?php

declare(strict_types=1);

namespace HyperfTests\Integration\DataPermission;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;
use Plugin\ExportCenter\Model\ExportTask;
use Plugin\ExportCenter\Service\DtoHydrator;
use Plugin\ExportCenter\Service\ExportAccessPolicy;
use Plugin\ExportCenter\Service\ExportDtoResolver;
use Plugin\ExportCenter\Service\ExportFileService;
use Plugin\ExportCenter\Service\ExportService;
use Psr\SimpleCache\CacheInterface;

final class ExportRevocationTest extends DatabaseTestCase
{
    private string $directory;
    private ExportService $service;
    private ExportFixtureProvider $provider;
    private DriverFactory $queue;

    protected function setUp(): void
    {
        parent::setUp();
        (new \Mine\Support\Listener\RegisterBlueprintListener())->process(new \stdClass());
        foreach (['2021_04_12_160526_create_user_table.php' => 'CreateUserTable', '2021_04_18_215515_create_role_table.php' => 'CreateRoleTable', '2021_04_18_215320_create_menu_table.php' => 'CreateMenuTable'] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        foreach (['2024_10_31_193302_create_user_belongs_role.php', '2024_10_31_204004_create_role_belongs_menu.php', '2025_02_24_195620_create_department_tables.php', '2025_02_18_000000_create_export_tables.php'] as $file) {
            $this->migrate('migrations/' . $file);
        }
        $this->connection->table('user')->insert(['id' => 7, 'username' => 'exporter', 'password' => 'fixture', 'status' => 1]);
        $this->connection->table('role')->insert(['id' => 1, 'name' => 'exporter', 'code' => 'exporter']);
        $this->connection->table('user_belongs_role')->insert(['user_id' => 7, 'role_id' => 1]);
        $this->connection->table('menu')->insert(['id' => 1, 'parent_id' => 0, 'name' => 'export:task:download']);
        $this->connection->table('role_belongs_menu')->insert(['role_id' => 1, 'menu_id' => 1]);
        $this->connection->table('department')->insert([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);
        $this->connection->table('user_dept')->insert(['user_id' => 7, 'dept_id' => 1]);
        $this->connection->table('data_permission_policy')->insert(['user_id' => 7, 'policy_type' => 'DEPT_SELF']);
        $this->directory = sys_get_temp_dir() . '/mine-export-' . bin2hex(random_bytes(8));
        $config = $this->container->get(ConfigInterface::class);
        $config->set('export.temp_path', $this->directory . '/temp');
        $config->set('export.private_path', $this->directory . '/private');
        $this->provider = new ExportFixtureProvider();
        $this->container->set(ExportFixtureProvider::class, $this->provider);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->willReturn('');
        $request->method('server')->willReturn('127.0.0.1');
        $this->container->set(RequestInterface::class, $request);
        $this->queue = $this->createMock(DriverFactory::class);
        $this->service = new ExportService(new ExportDtoResolver(), new DtoHydrator(), $this->createMock(CacheInterface::class), $this->queue, new ExportFileService(), new ExportAccessPolicy());
    }

    protected function tearDown(): void
    {
        if (isset($this->directory) && is_dir($this->directory)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function testPrivateDownloadRejectsOtherUsersAndLivePolicyChanges(): void
    {
        $task = $this->generate();
        $file = $this->service->getDownloadFile($task->id, 7);
        self::assertStringContainsString('sensitive row', file_get_contents($file['path']));
        self::assertSame(0600, fileperms($file['path']) & 0777);
        self::assertArrayNotHasKey('file_path', $this->service->getTaskDetail($task->id, 7));
        $this->assertDenied($task->id, 8, '无权限');
        $this->connection->table('user_dept')->where('user_id', 7)->update(['dept_id' => 2]);
        $this->assertDenied($task->id, 7, '权限已变更');
        $this->connection->table('user_dept')->where('user_id', 7)->update(['dept_id' => 1]);
        $this->connection->table('role_belongs_menu')->delete();
        $this->assertDenied($task->id, 7, '权限已变更');
    }

    public function testDisableExpiryAndLegacyPublicFilesCannotBeDownloaded(): void
    {
        $task = $this->generate();
        $this->connection->table('user')->where('id', 7)->update(['status' => 2]);
        $this->assertDenied($task->id, 7, '已停用');
        $this->connection->table('user')->where('id', 7)->update(['status' => 1]);
        $task->update(['expired_at' => '2020-01-01 00:00:00']);
        $this->assertDenied($task->id, 7, '已过期');
        $task->update(['expired_at' => null, 'file_path' => 'https://storage.example/old-export.csv']);
        $this->assertDenied($task->id, 7, '旧版');
        self::assertArrayNotHasKey('file_path', $this->service->getTaskDetail($task->id, 7));
    }

    public function testRevocationDuringGenerationFailsAndRemovesTemporaryFiles(): void
    {
        $this->provider->duringRead = function (): void {
            $this->connection->table('data_permission_policy')->where('user_id', 7)->update(['policy_type' => 'SELF']);
        };
        try {
            $this->generate();
            self::fail('Changed policy must reject the generated file');
        } catch (\DomainException $error) {
            self::assertStringContainsString('导出期间数据权限已变更', $error->getMessage());
        }
        self::assertSame('failed', $this->connection->table('export_tasks')->value('status'));
        self::assertNull($this->connection->table('export_tasks')->value('file_path'));
        self::assertSame([], glob($this->directory . '/temp/*'));
    }

    public function testDeletingTaskRemovesOnlyItsPrivateFile(): void
    {
        $task = $this->generate();
        $file = $this->service->getDownloadFile($task->id, 7);
        self::assertTrue($this->service->deleteTask($task->id, 7));
        self::assertFileDoesNotExist($file['path']);
    }

    public function testRetryRemovesOldPrivateArtifactBeforeRegenerating(): void
    {
        $task = $this->generate();
        $file = $this->service->getDownloadFile($task->id, 7);
        $task->update(['status' => 'failed']);
        $driver = $this->createMock(\Hyperf\AsyncQueue\Driver\DriverInterface::class);
        $driver->expects(self::once())->method('push')->with(self::callback(static fn ($job) => $job->taskId === $task->id))->willReturn(true);
        $this->queue->method('get')->with('export')->willReturn($driver);
        $this->service->retryTask($task->id, 7);
        self::assertFileDoesNotExist($file['path']);
        self::assertNull($task->refresh()->file_path);
        $this->service->processExportTask($task->id);
        self::assertSame('success', $task->refresh()->status);
    }

    public function testInheritedPositionPolicyAndRoleChangesInvalidateDownloads(): void
    {
        $this->connection->table('data_permission_policy')->delete();
        $this->connection->table('position')->insert(['id' => 1, 'name' => 'Exporter', 'dept_id' => 1]);
        $this->connection->table('user_position')->insert(['user_id' => 7, 'position_id' => 1]);
        $this->connection->table('data_permission_policy')->insert(['position_id' => 1, 'policy_type' => 'ALL']);
        $task = $this->generate();
        $this->connection->table('data_permission_policy')->where('position_id', 1)->update(['policy_type' => 'SELF']);
        $this->assertDenied($task->id, 7, '权限已变更');
        $task = $this->generate();
        $this->connection->table('user_belongs_role')->delete();
        $this->assertDenied($task->id, 7, '权限已变更');
    }

    public function testDynamicPoliciesCannotCreateUnrevocableSnapshots(): void
    {
        $this->connection->table('data_permission_policy')->where('user_id', 7)->update(['policy_type' => 'CUSTOM_FUNC', 'value' => '["dynamic"]']);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('动态自定义数据权限');
        $this->generate();
    }

    public function testFileKeysCannotEscapePrivateStorage(): void
    {
        $this->expectException(\DomainException::class);
        (new ExportFileService())->path('private:' . str_repeat('a', 64) . ':../../secret.csv');
    }

    public function testDisabledRolesAndMenusRevokePermissionsAndSuperAdminBypass(): void
    {
        $user = \App\Infrastructure\Model\Permission\User::query()->findOrFail(7);
        self::assertTrue($user->hasPermission('export:task:download'));
        self::assertCount(1, $user->getPermissions());
        $this->connection->table('menu')->where('id', 1)->update(['status' => 2]);
        self::assertFalse($user->hasPermission('export:task:download'));
        self::assertCount(0, $user->getPermissions());
        $this->connection->table('menu')->where('id', 1)->update(['status' => 1]);
        $this->connection->table('role')->where('id', 1)->update(['code' => 'SuperAdmin']);
        self::assertTrue($user->isSuperAdmin());
        $this->connection->table('role')->where('id', 1)->update(['status' => 2]);
        self::assertFalse($user->isSuperAdmin());
        self::assertFalse($user->hasPermission('export:task:download'));
        self::assertCount(0, $user->getPermissions());
    }

    private function generate(): ExportTask
    {
        $task = ExportTask::query()->create(['user_id' => 7, 'task_name' => 'fixture', 'dto_class' => ExportFixtureRow::class, 'export_format' => 'csv', 'export_params' => [], 'status' => 'pending']);
        $this->service->processExportTask($task->id);
        return $task->refresh();
    }

    private function assertDenied(int $taskId, int $userId, string $message): void
    {
        try {
            $this->service->getDownloadFile($taskId, $userId);
            self::fail('Download should be denied');
        } catch (\DomainException $error) {
            self::assertStringContainsString($message, $error->getMessage());
        }
    }
}

#[ExportSheet(dataProvider: [ExportFixtureProvider::class, 'rows'])]
final class ExportFixtureRow
{
    #[ExportColumn(title: 'Name')]
    public string $name;
}

final class ExportFixtureProvider
{
    public ?\Closure $duringRead = null;

    public function rows(array $params): iterable
    {
        if ($this->duringRead !== null) {
            ($this->duringRead)();
        }
        yield ['name' => 'sensitive row'];
    }
}
