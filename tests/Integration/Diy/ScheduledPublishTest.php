<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Diy;

use HyperfTests\Support\DatabaseTestCase;

final class ScheduledPublishTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new \Mine\Support\Listener\RegisterBlueprintListener())->process(new \stdClass());
        foreach ([
            '2021_04_12_160526_create_user_table.php' => 'CreateUserTable',
            '2021_04_18_215515_create_role_table.php' => 'CreateRoleTable',
            '2021_04_18_215320_create_menu_table.php' => 'CreateMenuTable',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        foreach (['2024_10_31_193302_create_user_belongs_role.php', '2024_10_31_204004_create_role_belongs_menu.php', '2025_02_24_195620_create_department_tables.php'] as $file) {
            $this->migrate('migrations/' . $file);
        }
        $this->migrate('plugins/diy/Database/Migrations/2026_06_06_000001_create_diy_page_tables.php');
        $this->migrate('plugins/diy/Database/Migrations/2026_06_06_000003_create_diy_publish_governance_tables.php');
        foreach ([1, 2, 3] as $id) {
            $this->connection->table('user')->insert(['id' => $id, 'username' => 'diy' . $id, 'password' => 'fixture']);
            $this->connection->table('user_belongs_role')->insert(['user_id' => $id, 'role_id' => 1]);
            $this->connection->table('data_permission_policy')->insert(['user_id' => $id, 'policy_type' => 'SELF', 'value' => '[]']);
        }
        $this->connection->table('role')->insert(['id' => 1, 'code' => 'Editor', 'name' => 'Editor']);
        $this->connection->table('menu')->insert(['id' => 1, 'parent_id' => 0, 'name' => 'mall:diy:page:publish']);
        $this->connection->table('role_belongs_menu')->insert(['role_id' => 1, 'menu_id' => 1]);
    }

    public function testScheduledPublishingThroughRealAopRespectsRevocationAndPreservesScheduledDraft(): void
    {
        $process = proc_open([PHP_BINARY, __DIR__ . '/scheduled-publish-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName()], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        self::assertSame(['published' => 1, 'checks' => 16], json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testConcurrentDuplicateDeliveryPublishesExactlyOnce(): void
    {
        $this->connection->table('diy_pages')->insert(['id' => 1, 'page_key' => 'home', 'title' => 'Concurrent', 'page_type' => 'all', 'created_by' => 1]);
        $this->connection->table('diy_page_versions')->insert(['id' => 1, 'page_id' => 1, 'version_no' => 1, 'status' => 'draft', 'schema' => json_encode(['version' => 1, 'page' => ['key' => 'home'], 'components' => []])]);
        $this->connection->table('diy_page_publish_records')->insert(['id' => 1, 'page_id' => 1, 'version_id' => 1, 'operator_id' => 1, 'publish_type' => 'scheduled', 'publish_status' => 'pending', 'scheduled_at' => '2020-01-01 00:00:00']);
        $workers = [];
        for ($i = 0; $i < 2; ++$i) {
            $process = proc_open([PHP_BINARY, __DIR__ . '/scheduled-publish-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName(), 'run-only'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $published = 0;
        foreach ($workers as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $stderr . $stdout);
            $published += json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR)['published'];
        }
        self::assertSame(1, $published);
        self::assertSame('published', $this->connection->table('diy_page_publish_records')->where('id', 1)->value('publish_status'));
    }

}
