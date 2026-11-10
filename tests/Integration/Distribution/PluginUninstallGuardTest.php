<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Distribution;

use Plugin\Distribution\Infrastructure\PluginUninstallGuard;
use Hyperf\Database\Schema\Blueprint;
use HyperfTests\Support\DatabaseTestCase;

final class PluginUninstallGuardTest extends DatabaseTestCase
{
    private array $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = json_decode(file_get_contents(BASE_PATH . '/plugins/distribution/plugin.json'), true, flags: JSON_THROW_ON_ERROR);
        $schema = $this->connection->getSchemaBuilder();
        $schema->create('distribution_rule_versions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->json('settings');
        });
        $schema->create('distribution_order_snapshots', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->dateTime('paid_at')->nullable();
        });
        $schema->create('order_submissions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('status');
            $table->json('payload');
        });
    }

    public function testUnusedPluginCanBeUninstalledAndOrdinarySubmissionsDoNotBlock(): void
    {
        $this->connection->table('distribution_rule_versions')->insert(['settings' => json_encode(['enabled' => false])]);
        $this->connection->table('order_submissions')->insert(['status' => 'pending', 'payload' => json_encode(['distribution' => []])]);
        (new PluginUninstallGuard())->assertCanUninstall($this->definition);
        $this->addToAssertionCount(1);
    }

    public function testPreviouslyEnabledBusinessCannotLoseItsHandlerAfterBeingDisabled(): void
    {
        foreach ([true, false] as $enabled) {
            $this->connection->table('distribution_rule_versions')->insert(['settings' => json_encode(['enabled' => $enabled])]);
        }
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('曾启用财务业务');
        (new PluginUninstallGuard())->assertCanUninstall($this->definition);
    }

    public function testPaidUnprocessedAndHistoricalSnapshotsAlwaysRetainHandler(): void
    {
        $this->connection->table('distribution_order_snapshots')->insert(['order_id' => 7, 'paid_at' => null]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('已有业务记录');
        (new PluginUninstallGuard())->assertCanUninstall($this->definition);
    }

    public function testQueuedAttributedSubmissionIsProtectedBeforeOrderExists(): void
    {
        $this->connection->table('order_submissions')->insert(['status' => 'reserved', 'payload' => json_encode(['distribution' => ['buyer_id' => 7, 'member_id' => 2]])]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('待处理的归因订单');
        (new PluginUninstallGuard())->assertCanUninstall($this->definition);
    }
}
