<?php

declare(strict_types=1);

namespace HyperfTests\Integration\CustomerServiceTransport;

use HyperfTests\Support\DatabaseTestCase;
use Plugin\CustomerService\Infrastructure\Service\CustomerServiceAuthorization;

final class AuthorizationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->statement('CREATE TABLE menu (id BIGINT AUTO_INCREMENT PRIMARY KEY, parent_id BIGINT DEFAULT 0, name VARCHAR(50) UNIQUE, meta JSON, path VARCHAR(60) DEFAULT "", component VARCHAR(150) DEFAULT "", redirect VARCHAR(100) DEFAULT "", status INT DEFAULT 1, sort INT DEFAULT 0, created_by BIGINT DEFAULT 0, updated_by BIGINT DEFAULT 0, remark VARCHAR(60) DEFAULT "", created_at DATETIME NULL, updated_at DATETIME NULL)');
        $this->connection->statement('CREATE TABLE role_belongs_menu (id BIGINT AUTO_INCREMENT PRIMARY KEY, role_id BIGINT, menu_id BIGINT, created_at DATETIME NULL, updated_at DATETIME NULL)');
        $this->connection->statement('CREATE TABLE `user` (id BIGINT PRIMARY KEY, status INT)');
        $this->connection->statement('CREATE TABLE role (id BIGINT PRIMARY KEY, code VARCHAR(50), status INT)');
        $this->connection->statement('CREATE TABLE user_belongs_role (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, role_id BIGINT)');
        $this->migrate('migrations/2026_08_21_000001_create_customer_service_tables.php');
        $this->connection->table('menu')->insert([
            ['id' => 1, 'name' => 'customer-service:conversation:read'],
            ['id' => 2, 'name' => 'customer-service:conversation:accept'],
        ]);
        $this->connection->table('role_belongs_menu')->insert([
            ['role_id' => 1, 'menu_id' => 1], ['role_id' => 2, 'menu_id' => 1], ['role_id' => 2, 'menu_id' => 2],
        ]);
        $this->connection->table('user')->insert(['id' => 7, 'status' => 1]);
        $this->connection->table('role')->insert(['id' => 1, 'code' => 'reader', 'status' => 1]);
        $this->connection->table('user_belongs_role')->insert(['user_id' => 7, 'role_id' => 1]);
        $this->connection->table('customer_service_agents')->insert(['id' => 3, 'admin_user_id' => 7, 'display_name' => '客服', 'enabled' => true]);
    }

    public function testFreshMenuSeedDefinesPermissionsWithoutGrantingReplyToReadOnlyRoles(): void
    {
        require_once BASE_PATH . '/seeders/menu_seeder_20240926.php';
        $this->connection->table('role_belongs_menu')->delete();
        // 全新部署通过原始菜单种子定义权限，不再复制旧角色的接待授权。
        (new \MenuSeeder20240926())->run();
        foreach (['customer-service:conversation:reply', 'customer-service:config:manage', 'customer-service:conversation:manage'] as $permission) {
            self::assertSame(1, $this->connection->table('menu')->where('name', $permission)->count());
        }
        $readId = $this->connection->table('menu')->where('name', 'customer-service:conversation:read')->value('id');
        $replyId = $this->connection->table('menu')->where('name', 'customer-service:conversation:reply')->value('id');
        self::assertNotNull($readId);
        self::assertSame(0, $this->connection->table('role_belongs_menu')->count());
        $this->connection->table('role_belongs_menu')->insert(['role_id' => 1, 'menu_id' => $readId]);
        $authorization = new CustomerServiceAuthorization();
        self::assertSame(3, $authorization->authorizeAgentAction(7, 3, 'read')['id']);
        $this->rejects(fn () => $authorization->authorizeAgentAction(7, 3, 'message:send'));
        $this->connection->table('role_belongs_menu')->insert(['role_id' => 1, 'menu_id' => $replyId]);
        self::assertSame(3, $authorization->authorizeAgentAction(7, 3, 'message:send')['id']);
    }

    public function testAuthorizationRechecksAccountAgentRoleAndActionOnEveryFrame(): void
    {
        $authorization = new CustomerServiceAuthorization();
        self::assertSame(3, $authorization->authorizeAgentAction(7, 3, 'read')['id']);
        $this->rejects(fn () => $authorization->authorizeAgentAction(7, 3, 'message:send'));
        $this->connection->table('role')->where('id', 1)->update(['status' => 2]);
        $this->rejects(fn () => $authorization->authorizeAgentAction(7, 3, 'read'));
        $this->connection->table('role')->where('id', 1)->update(['status' => 1, 'code' => 'SuperAdmin']);
        self::assertSame(3, $authorization->authorizeAgentAction(7, 3, 'message:send')['id']);
        $this->connection->table('customer_service_agents')->where('id', 3)->update(['enabled' => false]);
        $this->rejects(fn () => $authorization->authorizeAgentAction(7, 3, 'message:send'));
        $this->connection->table('customer_service_agents')->where('id', 3)->update(['enabled' => true]);
        $this->connection->table('user')->where('id', 7)->update(['status' => 2]);
        $this->rejects(fn () => $authorization->authorizeAgentAction(7, 3, 'read'));
    }

    private function rejects(callable $operation): void
    {
        try { $operation(); self::fail('An unauthorized command must be rejected.'); }
        catch (\DomainException $exception) { self::assertNotSame('', $exception->getMessage()); }
    }
}
