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
$base = '/tmp/mine-shop-tests.scope-' . bin2hex(random_bytes(6));
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
foreach (glob($source . '/config/*.php') as $file) {
    copy($file, $base . '/config/' . basename($file));
}
foreach (glob($source . '/config/autoload/*.php') as $file) {
    copy($file, $base . '/config/autoload/' . basename($file));
}
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
    $user = \App\Infrastructure\Model\Permission\User::findOrFail(1);
    \Hyperf\Context\Context::set('current_user', $user);
    \Hyperf\Context\Context::set('data_permission_enabled', true);
    $check = static function ($expected, $actual): void {
        if ($expected !== $actual) {
            throw new RuntimeException('Expected ' . json_encode($expected) . ', got ' . json_encode($actual));
        }
    };
    $ids = static fn ($query) => $query->orderBy('id')->pluck('id')->map(static fn ($id) => (int) $id)->all();
    $query = $connection->table('products')->where('id', 1)->orWhere('id', 2);
    $check([1], $ids($query));
    $check([1], $ids($query)); // Reusing a builder must not accumulate constraints.
    $check(false, $connection->table('products')->where('id', 2)->exists());
    $check(0, $connection->table('products')->where('id', 2)->update(['name' => 'forbidden']));
    $check(0, $connection->table('products')->where('id', 2)->delete());
    $check([1], $ids($connection->table('products as p')->select('p.id')));
    $check([11], $ids($connection->table('product_skus')));
    $check(0, $connection->table('product_skus')->where('id', 12)->update(['name' => 'forbidden']));
    $check([1], $ids($connection->table('products')->select('id')->union($connection->table('products')->select('id')->where('id', 2))));
    $check([1], array_map(static fn ($row) => (int) $row->id, iterator_to_array($connection->table('products')->orderBy('id')->cursor())));
    $authors = new \App\Infrastructure\Listener\ModelAuthorListener();
    $sku = new \App\Infrastructure\Model\Product\ProductSku(['product_id' => 2]);
    try {
        $authors->process(new \Hyperf\Database\Model\Events\Creating($sku));
        throw new RuntimeException('foreign parent accepted');
    } catch (DomainException) {
    }
    $sku->product_id = 1;
    $authors->process(new \Hyperf\Database\Model\Events\Creating($sku));
    $product = new \App\Infrastructure\Model\Product\Product(['created_by' => 2]);
    $authors->process(new \Hyperf\Database\Model\Events\Creating($product));
    $check(1, $product->created_by);
    // Department policies are read afresh, including empty custom selections and revocation.
    $connection->table('data_permission_policy')->insert(['user_id' => 1, 'policy_type' => 'CUSTOM_DEPT', 'value' => '[2]']);
    $check([2], $ids($connection->table('products')));
    $connection->table('data_permission_policy')->where('user_id', 1)->update(['value' => '[]']);
    $check([], $ids($connection->table('products')));
    $connection->table('data_permission_policy')->where('user_id', 1)->update(['policy_type' => 'DEPT_SELF']);
    $check([1, 3], $ids($connection->table('products')));
    $connection->table('data_permission_policy')->where('user_id', 1)->update(['policy_type' => 'DEPT_TREE']);
    $check([1, 2, 3], $ids($connection->table('products')));
    $connection->table('data_permission_policy')->where('user_id', 1)->update(['policy_type' => 'ALL']);
    $check([1, 2, 3, 4], $ids($connection->table('products')));
    $connection->table('data_permission_policy')->where('user_id', 1)->update(['policy_type' => 'SELF']);
    $check([1], $ids($connection->table('products')));
    // Scope is request-local and disabled for public API/background system work.
    \Hyperf\Context\Context::destroy('data_permission_enabled');
    $check([1, 2, 3, 4], $ids($connection->table('products')));
    echo json_encode(['checks' => 16, 'proxied' => str_contains((string) (new ReflectionMethod(\Hyperf\Database\Query\Builder::class, 'update'))->getFileName(), '/runtime/container/proxy/')], JSON_THROW_ON_ERROR) . PHP_EOL;
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
