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
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;
use SinceLeoo\Plugin\Contract\PluginManagerInterface;
use SinceLeoo\Plugin\PluginBootstrap;

// Only the disabled manifest and install lock below are physical, disposable test copies.
[$script, $socket, $database] = $argv;
if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/mysql.sock') || ! preg_match('/^codex_test_[a-z0-9_]+$/D', $database)) {
    throw new RuntimeException('Private test database required');
}
$source = dirname(__DIR__, 3);
$base = '/tmp/mine-shop-tests.lifecycle-' . bin2hex(random_bytes(6));
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
mkdir($base . '/plugins/supply-chain');
foreach (glob($source . '/config/*.php') as $file) {
    copy($file, $base . '/config/' . basename($file));
}
foreach (glob($source . '/config/autoload/*.php') as $file) {
    copy($file, $base . '/config/autoload/' . basename($file));
}
$definition = json_decode(file_get_contents($source . '/plugins/supply-chain/plugin.json'), true, 512, \JSON_THROW_ON_ERROR);
$definition['enabled'] = false;
file_put_contents($base . '/plugins/supply-chain/plugin.json', json_encode($definition));
$lock = $base . '/plugins/supply-chain/install.lock';
$marker = json_encode(['fixture' => bin2hex(random_bytes(8))]);
file_put_contents($lock, $marker);
file_put_contents($base . '/config/autoload/databases.php', '<?php return ' . var_export(['default' => ['driver' => 'mysql', 'unix_socket' => $socket, 'database' => $database, 'username' => 'root', 'password' => '', 'prefix' => '']], true) . ';');
file_put_contents($base . '/config/autoload/redis.php', '<?php return ["default" => ["host" => "/tmp/mine-shop-tests.invalid/redis.sock", "port" => 0, "db" => 0]];');
require $base . '/vendor/autoload.php';
try {
    PluginBootstrap::init();
    ClassLoader::init();
    $container = require $base . '/config/container.php';
    $pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection = new MySqlConnection($pdo, $database, '', ['name' => 'default', 'driver' => 'mysql']);
    $container->set(ConnectionResolverInterface::class, new ConnectionResolver(['default' => $connection]));
    $container->set(Db::class, new Db($container));
    $discoverer = $container->get(PluginDiscovererInterface::class);
    if (realpath((string) $discoverer->getPluginPath('since/supply-chain')) !== realpath($base . '/plugins/supply-chain') || is_link($lock)) {
        throw new RuntimeException('Unsafe uninstall target');
    }
    $manager = $container->get(PluginManagerInterface::class);
    $result = ['proxied' => str_contains((string) (new ReflectionMethod($manager, 'uninstall'))->getFileName(), '/runtime/container/proxy/')];
    try {
        $result['returned'] = $manager->uninstall('since/supply-chain', true, true);
    } catch (DomainException $error) {
        $result['exception'] = $error::class;
    }
    $result['lock_unchanged'] = is_file($lock) && file_get_contents($lock) === $marker;
    echo json_encode($result, \JSON_THROW_ON_ERROR) . \PHP_EOL;
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
