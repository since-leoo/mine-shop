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
use Plugin\Distribution\Domain\Service\DomainWithdrawalService;
use Plugin\Distribution\Interface\Dto\WithdrawalDto;
use Psr\EventDispatcher\EventDispatcherInterface;

[$script, $socket, $database, $memberId, $key, $readyFile] = $argv;
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
$repository = new DistributionRepository();
$service = new DomainWithdrawalService($repository);
try {
    $result = $connection->transaction(function () use ($connection, $service, $memberId, $key, $readyFile): array {
        // Establish a pre-lock repeatable-read snapshot to expose stale idempotency reads.
        $connection->table('distribution_withdrawals')->count();
        file_put_contents($readyFile, 'ready');
        return $service->apply((int) $memberId, new WithdrawalDto(['amount' => 500, 'account_name' => '测试收款人', 'account_no' => 'test-bank', 'request_key' => $key]));
    });
    echo json_encode(['accepted' => true, 'id' => (int) $result['id']], JSON_THROW_ON_ERROR);
} catch (DomainException $exception) {
    echo json_encode(['accepted' => false, 'reason' => $exception->getMessage()], JSON_THROW_ON_ERROR);
}
