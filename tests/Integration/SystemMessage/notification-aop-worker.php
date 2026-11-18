<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
use Hyperf\Database\ConnectionResolver;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Db;
use Hyperf\Di\ClassLoader;
use SinceLeoo\Plugin\PluginBootstrap;

// Load real AOP in a disposable application tree without application .env or real service pools.
[$script, $socket, $database, $messageId] = $argv;
if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/mysql.sock') || ! preg_match('/^codex_test_[a-z0-9_]+$/D', $database)) {
    throw new RuntimeException('Private test database required');
}
$source = dirname(__DIR__, 3);
$base = '/tmp/mine-shop-tests.notification-' . bin2hex(random_bytes(6));
mkdir($base, 0o700);
define('BASE_PATH', $base);
define('SWOOLE_HOOK_FLAGS', 0);
define('START_TIME', time());
define('HF_VERSION', '3.1');
date_default_timezone_set('Asia/Shanghai');
putenv('JWT_SECRET=' . base64_encode(str_repeat('fixture-only-', 8)));
foreach (['vendor', 'composer.json', 'composer.lock', 'app'] as $item) {
    symlink($source . '/' . $item, $base . '/' . $item);
}
mkdir($base . '/config');
mkdir($base . '/config/autoload');
mkdir($base . '/plugins');
foreach (['system-message', 'sms', 'wechat'] as $plugin) { symlink($source . '/plugins/' . $plugin, $base . '/plugins/' . $plugin); }
foreach (glob($source . '/config/*.php') as $file) {
    copy($file, $base . '/config/' . basename($file));
}
foreach (glob($source . '/config/autoload/*.php') as $file) {
    copy($file, $base . '/config/autoload/' . basename($file));
}
file_put_contents($base . '/config/autoload/databases.php', '<?php return ' . var_export(['default' => ['driver' => 'mysql', 'unix_socket' => $socket, 'database' => $database, 'username' => 'root', 'password' => '', 'prefix' => '']], true) . ';');
file_put_contents($base . '/config/autoload/redis.php', '<?php return ["default" => ["host" => "/tmp/mine-shop-tests.invalid/redis.sock", "port" => 0, "db" => 0]];');
$loader = require $base . '/vendor/autoload.php';
mkdir($base . '/fixtures');
file_put_contents($base . '/fixtures/QueueBool.php', '<?php namespace NotificationAopFixture; class QueueBool { #[\\Hyperf\\AsyncQueue\\Annotation\\AsyncQueueMessage] public function run(): bool { return true; } }');
$loader->addPsr4('NotificationAopFixture\\', $base . '/fixtures');
file_put_contents($base . '/config/autoload/annotations.php', '<?php return ' . var_export(['scan' => ['paths' => [$base . '/app', $base . '/plugins/system-message/src', $base . '/fixtures'], 'collectors' => [], 'ignore_annotations' => []]], true) . ';');
try {
    PluginBootstrap::init();
    ClassLoader::init();
    $container = require $base . '/config/container.php';
    $pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection = new MySqlConnection($pdo, $database, '', ['name' => 'default', 'driver' => 'mysql']);
    $container->set(ConnectionResolverInterface::class, new ConnectionResolver(['default' => $connection]));
    $container->set(Db::class, new Db($container));
    $config = $container->get(\Hyperf\Contract\ConfigInterface::class);
    $config->set('system_message.notification.default_channels', ['database' => true]);
    $logger = \Mockery::mock(\Hyperf\Logger\LoggerFactory::class);
    $logger->shouldReceive('make')->andReturn(new \Psr\Log\NullLogger());
    $container->set(\Hyperf\Logger\LoggerFactory::class, $logger);
    $container->set(\Psr\Log\LoggerInterface::class, new \Psr\Log\NullLogger());
    $container->set(\Psr\EventDispatcher\EventDispatcherInterface::class, new class implements \Psr\EventDispatcher\EventDispatcherInterface {
        public function dispatch(object $event): object { return $event; }
    });
    $preferences = \Mockery::mock(\Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\UserPreferenceRepository::class);
    $preferences->shouldReceive('getUserPreference')->andReturn(null);
    $service = new \Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\NotificationService(
        $preferences,
        \Mockery::mock(\Plugin\Sms\Service\SmsSenderInterface::class),
        \Mockery::mock(\Plugin\Wechat\Interfaces\MiniAppInterface::class),
        \Mockery::mock(\Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\NotificationTransportInterface::class),
    );
    $container->set(\Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\NotificationService::class, $service);
    $container->get(\Hyperf\AsyncQueue\Environment::class)->setAsyncQueue(true);
    $aspectActive = false;
    try {
        (new \NotificationAopFixture\QueueBool())->run();
    } catch (\TypeError) {
        $aspectActive = true;
    }
    if (! $aspectActive) {
        throw new RuntimeException('The real AsyncQueueAspect must be active for the regression fixture');
    }
    $job = new \Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Job\SendMessageJob((int) $messageId, 7, 'database');
    if (($argv[4] ?? '') === 'concurrent') {
        echo "ready\n";
        flush();
    }
    $job->handle();
    $job->handle();
    $sent = $service->send(\Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message::query()->findOrFail((int) $messageId), 7, 'database');
    echo json_encode(['sent' => $sent, 'deliveries' => $connection->table('message_delivery_logs')->where('message_id', (int) $messageId)->count(), 'async_aspect_active' => $aspectActive], JSON_THROW_ON_ERROR) . PHP_EOL;
    \Mockery::close();
} finally {
    $remove = static function (string $path) use (&$remove): void {
        if (is_dir($path) && ! is_link($path)) {
            foreach (scandir($path) as $item) {
                if ($item !== '.' && $item !== '..') {
                    $remove($path . '/' . $item);
                }
            } rmdir($path);
        } else {
            unlink($path);
        }
    };
    $remove($base);
}
