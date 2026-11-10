<?php

declare(strict_types=1);

namespace HyperfTests\Integration\PluginExtraction;

use App\Infrastructure\Plugin\InstalledPluginMigratorFactory;
use App\Infrastructure\Plugin\PluginConfigReader;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Database\Commands\Migrations\InstallCommand;
use Hyperf\Database\Commands\Migrations\MigrateCommand;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Migrations\DatabaseMigrationRepository;
use Hyperf\Database\Migrations\MigrationRepositoryInterface;
use Hyperf\Database\Migrations\Migrator;
use Hyperf\Support\Filesystem\Filesystem;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\MessageDeliveryLog;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserMessage;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserNotificationPreference;
use SinceLeoo\Plugin\Command\PluginInstallCommand;
use SinceLeoo\Plugin\ConfigWriter;
use SinceLeoo\Plugin\Contract\PluginConfigReaderInterface;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;
use SinceLeoo\Plugin\Contract\PluginManagerInterface;
use SinceLeoo\Plugin\MigrationRunner;
use SinceLeoo\Plugin\PluginDiscoverer;
use SinceLeoo\Plugin\PluginManager;
use SinceLeoo\Plugin\PluginRepository;
use SinceLeoo\Plugin\SeederRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class InstalledPluginMigrationTest extends DatabaseTestCase
{
    private ?string $directory = null;

    protected function tearDown(): void
    {
        if ($this->directory !== null) { (new Filesystem())->deleteDirectory($this->directory); }
        parent::tearDown();
    }

    public function testStandardMigrateCreatesBundledMessageSchemaDespiteEmptyInstallLock(): void
    {
        $application = $this->application(true);
        $output = new BufferedOutput();
        self::assertSame(0, $application->run(new ArrayInput(['command' => 'migrate', '--force' => true]), $output), $output->fetch());
        self::assertSame(5, $this->connection->table('migrations')->count());
        self::assertSame(0, UserMessage::getUnreadCount(1));
        $this->assertMessageWorkflowAndComments();
        // Database migration history, rather than install.lock, prevents a second CREATE TABLE.
        self::assertSame(0, $application->run(new ArrayInput(['command' => 'migrate', '--force' => true]), $output), $output->fetch());
        self::assertSame(5, $this->connection->table('migrations')->count());
        self::assertSame(1, Message::count());
    }

    public function testPluginInstallCommandRunsLowercaseMigrationsOnFreshDatabase(): void
    {
        $application = $this->application(false);
        $command = new PluginInstallCommand($this->container);
        (new \ReflectionProperty($command, 'coroutine'))->setValue($command, false);
        $application->add($command);
        $output = new BufferedOutput();
        self::assertSame(0, $application->run(new ArrayInput(['command' => 'plugin:install', 'pluginName' => 'since/system-message', '--no-seed' => true]), $output), $output->fetch());
        $lock = json_decode(file_get_contents($this->directory . '/plugins/system-message/install.lock'), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(5, $lock['migrations_executed']);
        self::assertSame(5, $this->connection->table('migrations')->count());
        $this->assertMessageWorkflowAndComments();
    }

    private function application(bool $installed): Application
    {
        $this->directory = sys_get_temp_dir() . '/mine-message-install-' . bin2hex(random_bytes(6));
        $path = $this->directory . '/plugins/system-message';
        mkdir($path . '/migrations', 0777, true);
        copy(BASE_PATH . '/plugins/system-message/plugin.json', $path . '/plugin.json');
        $reader = new PluginConfigReader();
        $sourcePath = $reader->getMigrationPath(BASE_PATH . '/plugins/system-message');
        self::assertNotNull($sourcePath, 'The actual system-message migration source must exist');
        $files = glob($sourcePath . '/*.php') ?: [];
        self::assertNotEmpty($files, 'The fixture must copy real migration sources');
        // The repository uses Database/Migrations; lowercase here deliberately verifies compatibility.
        foreach ($files as $file) { copy($file, $path . '/migrations/' . basename($file)); }
        if ($installed) { file_put_contents($path . '/install.lock', '{"migrations_executed":[]}'); }
        $writer = new ConfigWriter($this->directory . '/not-present.php');
        $discoverer = new PluginDiscoverer($reader, $writer, $this->directory);
        $repository = new DatabaseMigrationRepository($this->container->get(ConnectionResolverInterface::class), 'migrations');
        $this->container->set(MigrationRepositoryInterface::class, $repository);
        $this->container->set(Filesystem::class, new Filesystem());
        $this->container->set(PluginConfigReaderInterface::class, $reader);
        $this->container->set(PluginDiscovererInterface::class, $discoverer);
        $migrator = (new InstalledPluginMigratorFactory())($this->container);
        $application = new class extends Application implements ApplicationInterface {};
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        // This focused test keeps core migrations out; the standard path selection itself is unchanged.
        $migrate = new class($migrator) extends MigrateCommand {
            protected bool $coroutine = false;
            protected function getMigrationPath(): string { return '/__no_core_migrations_in_plugin_test__'; }
        };
        $install = new InstallCommand($repository);
        (new \ReflectionProperty($install, 'coroutine'))->setValue($install, false);
        $application->add($migrate);
        $application->add($install);
        $this->container->set(ApplicationInterface::class, $application);
        $manager = new PluginManager($discoverer, new PluginRepository($writer), $writer, $reader, new MigrationRunner($discoverer), new SeederRunner($discoverer));
        $this->container->set(PluginManagerInterface::class, $manager);
        return $application;
    }

    private function assertMessageWorkflowAndComments(): void
    {
        $message = Message::create(['title' => '新订单', 'content' => '请处理订单', 'type' => 'system', 'extra_data' => ['order_id' => 1], 'status' => 'sent']);
        $receipt = UserMessage::create(['user_id' => 1, 'message_id' => $message->id]);
        self::assertSame(1, UserMessage::getUnreadCount(1));
        self::assertTrue($receipt->markAsRead());
        self::assertSame(0, UserMessage::getUnreadCount(1));
        self::assertSame(['order_id' => 1], $message->fresh()->extra_data);
        foreach (MessageChannel::cases() as $channel) {
            MessageDeliveryLog::create(['message_id' => $message->id, 'user_id' => 1, 'channel' => $channel->value, 'status' => 'delivered']);
        }
        self::assertSame(count(MessageChannel::cases()), MessageDeliveryLog::count());
        $preference = UserNotificationPreference::create(['user_id' => 1, 'channel_preferences' => ['database' => true], 'type_preferences' => ['system' => true]]);
        self::assertTrue($preference->fresh()->shouldNotify('database', 'system'));
        $tables = $this->connection->select('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME <> ?', ['migrations']);
        self::assertCount(5, $tables);
        foreach ($tables as $table) { self::assertNotSame('', $table->TABLE_COMMENT, $table->TABLE_NAME); }
        $columns = $this->connection->select('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME <> ?', ['migrations']);
        foreach ($columns as $column) { self::assertNotSame('', $column->COLUMN_COMMENT, $column->TABLE_NAME . '.' . $column->COLUMN_NAME); }
    }
}
