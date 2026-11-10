<?php

declare(strict_types=1);

/** 完整 CLI 入口回归：只接受父测试创建的隔离空库，不加载业务 .env。 */
$source = dirname(__DIR__, 2);
$database = $argv[1] ?? '';
$mysqlSocket = (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET');
$redisSocket = (string) getenv('MINE_SHOP_TEST_REDIS_SOCKET');
if (!preg_match('/^codex_test_[0-9]+_[a-f0-9]+$/', $database)
    || !str_starts_with($mysqlSocket, '/tmp/mine-shop-tests.') || !str_ends_with($mysqlSocket, '/mysql.sock')
    || !str_starts_with($redisSocket, '/tmp/mine-shop-tests.') || !str_ends_with($redisSocket, '/redis.sock')) {
    throw new RuntimeException('Fresh deployment tests require disposable MySQL/Redis sockets and a random test database.');
}
$guard = new PDO('mysql:unix_socket=' . $mysqlSocket . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($guard->query('SHOW TABLES')->fetchAll() !== []) {
    throw new RuntimeException('Fresh deployment requires an empty database owned by DatabaseTestCase.');
}
$guard = null;

$base = '/tmp/mine-shop-tests.' . bin2hex(random_bytes(8));
mkdir($base, 0700);
$redisPrefix = 'codex:fresh:' . bin2hex(random_bytes(8)) . ':';
define('BASE_PATH', $base);
define('SWOOLE_HOOK_FLAGS', 0);
define('START_TIME', time());
define('HF_VERSION', '3.1');
date_default_timezone_set('Asia/Shanghai');
putenv('JWT_SECRET=' . base64_encode(random_bytes(96)));
putenv('APP_ENV=testing');
putenv('APP_DEBUG=false');

$remove = static function (string $path) use (&$remove): void {
    if (is_dir($path) && !is_link($path)) {
        foreach (scandir($path) as $item) {
            if ($item !== '.' && $item !== '..') { $remove($path . '/' . $item); }
        }
        rmdir($path);
    } elseif (is_file($path) || is_link($path)) {
        unlink($path);
    }
};

$exitCode = 0;
try {
    // 仅源码和迁移可共享；配置、扫描缓存和运行目录全部落在私有 BASE_PATH。
    foreach (['vendor', 'composer.json', 'composer.lock', 'app', 'kernel', 'migrations', 'seeders'] as $item) {
        if (file_exists($source . '/' . $item)) { symlink($source . '/' . $item, $base . '/' . $item); }
    }
    $copyConfig = static function (string $from, string $to) use (&$copyConfig): void {
        mkdir($to);
        foreach (scandir($from) as $item) {
            if ($item === '.' || $item === '..') { continue; }
            if (is_dir($from . '/' . $item)) {
                $copyConfig($from . '/' . $item, $to . '/' . $item);
            } elseif (str_ends_with($item, '.php')) {
                copy($from . '/' . $item, $to . '/' . $item);
            }
        }
    };
    $copyConfig($source . '/config', $base . '/config');
    mkdir($base . '/plugins');
    foreach (glob($source . '/plugins/*/plugin.json') as $file) {
        $target = $base . '/plugins/' . basename(dirname($file));
        mkdir($target);
        copy($file, $target . '/plugin.json');
        foreach (glob(dirname($file) . '/*') as $item) {
            if (basename($item) !== 'plugin.json') { symlink($item, $target . '/' . basename($item)); }
        }
    }
    $databaseConfig = ['default' => [
        'driver' => 'mysql', 'unix_socket' => $mysqlSocket, 'database' => $database,
        'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
        'pool' => ['min_connections' => 1, 'max_connections' => 5, 'connect_timeout' => 5.0, 'wait_timeout' => 3.0, 'heartbeat' => -1, 'max_idle_time' => 60],
    ]];
    $redisConfig = ['default' => [
        'host' => $redisSocket, 'port' => 0, 'auth' => null, 'db' => 0,
        'options' => [Redis::OPT_PREFIX => $redisPrefix],
        'pool' => ['min_connections' => 1, 'max_connections' => 5, 'connect_timeout' => 5.0, 'wait_timeout' => 3.0, 'heartbeat' => -1, 'max_idle_time' => 60],
    ]];
    file_put_contents($base . '/config/autoload/databases.php', '<?php return ' . var_export($databaseConfig, true) . ';');
    file_put_contents($base . '/config/autoload/redis.php', '<?php return ' . var_export($redisConfig, true) . ';');
    require $base . '/vendor/autoload.php';
    SinceLeoo\Plugin\PluginBootstrap::init();
    Hyperf\Di\ClassLoader::init();
    $container = require $base . '/config/container.php';
    $application = $container->get(Hyperf\Contract\ApplicationInterface::class);
    $application->setAutoExit(false);
    $application->setCatchExceptions(false);
    $runCommand = static function (string $command) use ($application): array {
        $output = new Symfony\Component\Console\Output\BufferedOutput();
        $input = new Symfony\Component\Console\Input\ArrayInput(['command' => $command, '--force' => true, '--no-interaction' => true]);
        $input->setInteractive(false);
        return ['exit' => $application->run($input, $output), 'output' => $output->fetch()];
    };
    // 不传 --path，使用生产命令注册的根迁移和全部已安装插件路径。
    $first = $runCommand('migrate');
    if ($first['exit'] !== 0) { throw new RuntimeException('Initial migrate failed: ' . $first['output']); }
    // 原 Seeder 只初始化当前随机库中的菜单、管理员及系统设置，不访问业务库或外部服务。
    $seed = $runCommand('db:seed');
    if ($seed['exit'] !== 0) { throw new RuntimeException('Initial db:seed failed: ' . $seed['output']); }
    $migrator = $container->get(Hyperf\Database\Migrations\Migrator::class);
    $paths = [...$migrator->paths(), BASE_PATH . '/migrations'];
    $discovered = array_keys($migrator->getMigrationFiles($paths));
    $migrationFiles = [];
    foreach ($paths as $path) {
        foreach (glob($path . '/*.php') as $file) { $migrationFiles[basename($file)][] = $file; }
    }
    $migrationNameConflicts = array_filter($migrationFiles, static fn (array $files): bool => count($files) > 1);
    $ranBefore = $migrator->getRepository()->getRan();
    $second = $runCommand('migrate');
    $ranAfter = $migrator->getRepository()->getRan();
    $installed = $container->get(SinceLeoo\Plugin\Contract\PluginDiscovererInterface::class)->getInstalledPlugins();
    $reader = $container->get(SinceLeoo\Plugin\Contract\PluginConfigReaderInterface::class);
    $pluginPaths = [];
    foreach ($installed as $plugin) {
        $path = $reader->getMigrationPath($plugin['path']);
        if ($path !== null) { $pluginPaths[basename($plugin['path'])] = realpath($path); }
    }

    // 调用真实模型/Worker，捕获迁移字段与运行期查询之间的不一致。
    $unread = Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserMessage::getUnreadCount(1);
    $worker = $container->get(Plugin\CustomerService\Application\Worker\CustomerServiceWorker::class);
    $worker->maintainQueue();
    $processedAi = $worker->processOneAiRun();
    $candidates = Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation::query()
        ->whereIn('reception_state', ['human_waiting', 'human_assigned', 'human_deferred', 'ai_active'])
        ->orderByDesc('priority')->orderBy('queue_entered_at')->limit(200)->pluck('id')->all();
    $permissions = App\Infrastructure\Model\Permission\Menu::query()
        ->whereIn('name', ['customer-service:conversation:reply', 'customer-service:config:manage', 'customer-service:conversation:manage'])
        ->pluck('name')->all();

    $commentFields = [
        'orders' => 'order_no', 'order_submissions' => 'payload', 'trade_outbox' => 'business_key',
        'payment_refunds' => 'status', 'order_trade_after_sale' => 'refund_status',
        'customer_service_conversations' => 'reception_state', 'customer_service_ai_runs' => 'generation',
        'user_messages' => 'is_read', 'reviews' => 'status', 'diy_pages' => 'title', 'stat_daily_sales' => 'date',
    ];
    $comments = [];
    foreach ($commentFields as $table => $field) {
        $metadata = Hyperf\DbConnection\Db::selectOne(
            'SELECT t.TABLE_COMMENT AS table_note, c.COLUMN_COMMENT AS field_note FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME WHERE t.TABLE_SCHEMA = ? AND t.TABLE_NAME = ? AND c.COLUMN_NAME = ?',
            [$database, $table, $field]
        );
        $comments[$table . '.' . $field] = $metadata === null ? null : (array) $metadata;
    }
    echo 'FRESH_DEPLOYMENT_RESULT=' . json_encode([
        'first_exit' => $first['exit'], 'seed_exit' => $seed['exit'], 'second_exit' => $second['exit'], 'second_output' => $second['output'],
        'discovered' => $discovered, 'ran_before' => $ranBefore, 'ran_after' => $ranAfter,
        'migration_name_conflicts' => $migrationNameConflicts,
        'registered_paths' => $migrator->paths(), 'plugin_paths' => $pluginPaths,
        'unread' => $unread, 'worker_candidates' => $candidates, 'processed_ai' => $processedAi,
        'comments' => $comments, 'private_base' => $base, 'permissions' => $permissions,
    ], JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
    $exitCode = 1;
} finally {
    // 独立前缀限定清理范围；不清空共用测试 Redis，也不停止测试服务。
    try {
        $redis = new Redis();
        if ($redis->connect($redisSocket)) {
            $keys = $redis->keys($redisPrefix . '*');
            if ($keys !== []) { $redis->del($keys); }
            $redis->close();
        }
    } finally {
        $remove($base);
    }
}
exit($exitCode);
