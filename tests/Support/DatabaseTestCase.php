<?php

declare(strict_types=1);

namespace HyperfTests\Support;

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolver;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Db;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/** Real MySQL tests, opt-in only; every test owns a new disposable database. */
abstract class DatabaseTestCase extends TestCase
{
    protected MySqlConnection $connection;

    protected TestContainer $container;

    private ?\PDO $admin = null;

    private ?string $testDatabase = null;

    private ?ContainerInterface $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $socket = getenv('MINE_SHOP_TEST_MYSQL_SOCKET');
        if (! is_string($socket) || $socket === '') {
            self::markTestSkipped('Set MINE_SHOP_TEST_MYSQL_SOCKET to an isolated local test server.');
        }
        // Never consult DB_HOST/DB_DATABASE/.env or reuse an existing database.
        if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/mysql.sock')) {
            throw new \RuntimeException('Tests require a disposable /tmp/mine-shop-tests.*/mysql.sock server.');
        }
        $this->previousContainer = ApplicationContext::hasContainer() ? ApplicationContext::getContainer() : null;
        $this->admin = new \PDO('mysql:unix_socket=' . $socket, 'root', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->testDatabase = 'codex_test_' . getmypid() . '_' . bin2hex(random_bytes(6));
        $this->admin->exec('CREATE DATABASE `' . $this->testDatabase . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = new \PDO('mysql:unix_socket=' . $socket . ';dbname=' . $this->testDatabase, 'root', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->connection = new MySqlConnection($pdo, $this->testDatabase, '', ['name' => 'default', 'driver' => 'mysql']);
        $resolver = new ConnectionResolver(['default' => $this->connection]);
        $events = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object { return $event; }
        };
        $this->container = new TestContainer([
            ConnectionResolverInterface::class => $resolver,
            ConfigInterface::class => new Config(['databases' => ['default' => ['driver' => 'mysql', 'prefix' => '']]]),
            EventDispatcherInterface::class => $events,
        ]);
        $this->container->set(Db::class, new Db($this->container));
        ApplicationContext::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->admin !== null && $this->testDatabase !== null) {
                $this->admin->exec('DROP DATABASE `' . $this->testDatabase . '`');
            }
        } finally {
            if ($this->previousContainer !== null) {
                ApplicationContext::setContainer($this->previousContainer);
            } else {
                (new \ReflectionProperty(ApplicationContext::class, 'container'))->setValue(null, null);
            }
            parent::tearDown();
        }
    }

    protected function migrate(string $relativePath): void
    {
        $migration = require BASE_PATH . '/' . $relativePath;
        $migration->up();
    }
}
