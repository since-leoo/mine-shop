<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap-unit.php';

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolver;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Db;
use HyperfTests\Support\TestContainer;
use Plugin\Distribution\Domain\Repository\DistributionRepository;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Psr\EventDispatcher\EventDispatcherInterface;

[$script, $socket, $database, $memberId, $inviteCode, $readyFile] = $argv;
if (!str_starts_with($socket, '/tmp/mine-shop-tests.') || !str_ends_with($socket, '/mysql.sock') || !preg_match('/^codex_test_\d+_[a-f0-9]+$/', $database)) {
    throw new RuntimeException('Concurrency worker requires the disposable test database.');
}
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$connection = new MySqlConnection($pdo, $database, '', ['name' => 'default', 'driver' => 'mysql']);
$events = new class implements EventDispatcherInterface { public function dispatch(object $event): object { return $event; } };
$container = new TestContainer([
    ConnectionResolverInterface::class => new ConnectionResolver(['default' => $connection]),
    ConfigInterface::class => new Config(['databases' => ['default' => ['driver' => 'mysql', 'prefix' => '']]]),
    EventDispatcherInterface::class => $events,
]);
$container->set(Db::class, new Db($container));
ApplicationContext::setContainer($container);
$repository = new class($readyFile) extends DistributionRepository {
    public function __construct(private readonly string $readyFile) {}
    public function directCount(int $id, bool $lock = false): int
    {
        $count = parent::directCount($id, $lock);
        if (!$lock) { file_put_contents($this->readyFile, 'ready'); }
        return $count;
    }
};
$service = new DomainDistributionService($repository);
$result = $connection->transaction(fn () => $service->bindNewMember((int) $memberId, $inviteCode, null));
echo json_encode($result, JSON_THROW_ON_ERROR);
