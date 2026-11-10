<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Deployment;

use HyperfTests\Support\DatabaseTestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class FreshDeploymentTest extends DatabaseTestCase
{
    public function testStandardMigrateInstallsCoreAndPluginsAndRuntimeQueriesWork(): void
    {
        if (!getenv('MINE_SHOP_TEST_REDIS_SOCKET')) { self::markTestSkipped('Set the isolated test Redis socket.'); }
        // 超时要回收子进程，避免启动、迁移或 Worker 死锁挂住整个集成测试。
        $process = new Process([PHP_BINARY, BASE_PATH . '/tests/Support/fresh-deployment.php', $this->connection->getDatabaseName()], BASE_PATH, timeout: 180);
        try {
            $exit = $process->run();
        } catch (ProcessTimedOutException $e) {
            self::fail($e->getMessage() . "\n" . $process->getErrorOutput() . "\n" . $process->getOutput());
        }
        $output = $process->getOutput();
        $error = $process->getErrorOutput();
        self::assertSame(0, $exit, $error . "\n" . $output);
        self::assertSame(1, preg_match('/^FRESH_DEPLOYMENT_RESULT=(.*)$/m', $output, $matches), $output);
        $result = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $result['first_exit']);
        self::assertSame(0, $result['seed_exit']);
        self::assertSame(0, $result['second_exit']);
        self::assertStringContainsString('Nothing to migrate', $result['second_output']);
        self::assertNotEmpty($result['discovered']);
        self::assertEqualsCanonicalizing($result['discovered'], $result['ran_before']);
        self::assertSame($result['ran_before'], $result['ran_after']);
        self::assertSame([], $result['migration_name_conflicts']);
        foreach (['system-message', 'analytics', 'diy', 'product-review'] as $plugin) {
            self::assertArrayHasKey($plugin, $result['plugin_paths']);
        }
        foreach ($result['plugin_paths'] as $path) { self::assertContains($path, $result['registered_paths']); }
        self::assertSame(0, $result['unread']);
        self::assertSame([], $result['worker_candidates']);
        self::assertFalse($result['processed_ai']);
        self::assertEqualsCanonicalizing(['customer-service:conversation:reply', 'customer-service:config:manage', 'customer-service:conversation:manage'], $result['permissions']);
        foreach ($result['comments'] as $column => $metadata) {
            self::assertNotNull($metadata, $column . ' must exist');
            self::assertNotSame('', trim($metadata['table_note']), $column . ' table comment');
            self::assertNotSame('', trim($metadata['field_note']), $column . ' field comment');
        }
        self::assertDirectoryDoesNotExist($result['private_base']);
    }
}
