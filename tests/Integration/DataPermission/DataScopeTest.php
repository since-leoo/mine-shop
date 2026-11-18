<?php

declare(strict_types=1);

namespace HyperfTests\Integration\DataPermission;

use Hyperf\Database\Schema\Blueprint;
use HyperfTests\Support\DatabaseTestCase;

final class DataScopeTest extends DatabaseTestCase
{
    public function testActualAopScopesReadsWritesChildrenUnionsAndCursorsWithLivePolicies(): void
    {
        (new \Mine\Support\Listener\RegisterBlueprintListener())->process(new \stdClass());
        require_once BASE_PATH . '/migrations/2021_04_12_160526_create_user_table.php';
        require_once BASE_PATH . '/migrations/2021_04_18_215515_create_role_table.php';
        (new \CreateUserTable())->up();
        (new \CreateRoleTable())->up();
        $this->migrate('migrations/2024_10_31_193302_create_user_belongs_role.php');
        $this->migrate('migrations/2025_02_24_195620_create_department_tables.php');
        foreach ([1, 2, 3] as $id) {
            $this->connection->table('user')->insert(['id' => $id, 'username' => 'user' . $id, 'password' => 'fixture']);
        }
        $this->connection->table('department')->insert([['id' => 1, 'name' => 'A', 'parent_id' => 0], ['id' => 2, 'name' => 'B', 'parent_id' => 1]]);
        $this->connection->table('user_dept')->insert([['user_id' => 1, 'dept_id' => 1], ['user_id' => 2, 'dept_id' => 2], ['user_id' => 3, 'dept_id' => 1]]);
        $this->connection->table('position')->insert(['id' => 1, 'name' => 'P', 'dept_id' => 1]);
        $positions = new \App\Domain\Organization\Service\DomainPositionService(new \App\Domain\Organization\Repository\PositionRepository(new \App\Infrastructure\Model\Permission\Position()));
        self::assertTrue($positions->setDataPermissionPolicy(1, \App\Domain\Permission\Enum\DataPermission\PolicyType::Self, []));
        self::assertSame('SELF', $this->connection->table('data_permission_policy')->where('position_id', 1)->value('policy_type'));
        foreach (['products', 'product_skus'] as $name) {
            $this->connection->getSchemaBuilder()->create($name, static function (Blueprint $table): void {
                $table->id();
                $table->string('name')->default('fixture');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
            });
        }
        $this->connection->table('products')->insert([['id' => 1, 'created_by' => 1], ['id' => 2, 'created_by' => 2], ['id' => 3, 'created_by' => 3], ['id' => 4, 'created_by' => null]]);
        $this->connection->table('product_skus')->insert([['id' => 11, 'product_id' => 1], ['id' => 12, 'product_id' => 2]]);
        $process = proc_open([PHP_BINARY, __DIR__ . '/scope-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName()], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        $result = json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['proxied']);
        self::assertSame(16, $result['checks']);
    }
}
