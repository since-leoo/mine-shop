<?php
declare(strict_types=1);
$source = dirname(__DIR__, 2);
$base = '/tmp/mine-shop-tests.' . bin2hex(random_bytes(6));
mkdir($base, 0700);
define('BASE_PATH', $base);
define('SWOOLE_HOOK_FLAGS', 0);
define('START_TIME', time());
define('HF_VERSION', '3.1');
date_default_timezone_set('Asia/Shanghai');
putenv('JWT_SECRET=' . base64_encode(str_repeat('fixture-only-', 8)));
foreach (['vendor','composer.json','composer.lock','app','kernel'] as $item) { if (file_exists($source . '/' . $item)) symlink($source . '/' . $item, $base . '/' . $item); }
mkdir($base . '/config');
mkdir($base . '/config/autoload');
foreach (glob($source . '/config/*.php') as $file) copy($file, $base . '/config/' . basename($file));
foreach (glob($source . '/config/autoload/*.php') as $file) copy($file, $base . '/config/autoload/' . basename($file));
mkdir($base . '/plugins');
$disabled = ($argv[1] ?? '') === 'disabled';
foreach (glob($source . '/plugins/*/plugin.json') as $file) {
    $name = basename(dirname($file));
    $target = $base . '/plugins/' . $name;
    mkdir($target);
    $manifest = json_decode(file_get_contents($file), true);
    if ($disabled && in_array($name, ['analytics','diy','product-review','system-message','distribution'])) $manifest['enabled'] = false;
    file_put_contents($target . '/plugin.json', json_encode($manifest));
    foreach (glob(dirname($file) . '/*') as $item) { if (basename($item) !== 'plugin.json') symlink($item, $target . '/' . basename($item)); }
}
file_put_contents($base . '/config/autoload/databases.php', '<?php return ["default" => ["driver"=>"mysql", "unix_socket"=>"/tmp/mine-shop-tests.invalid/mysql.sock", "database"=>"nonexistent_smoke", "username"=>"root", "password"=>"", "prefix"=>""]];');
file_put_contents($base . '/config/autoload/redis.php', '<?php return ["default" => ["host"=>"/tmp/mine-shop-tests.invalid/redis.sock", "port"=>0, "db"=>0, "options"=>[Redis::OPT_PREFIX=>"codex:smoke:"]]];');
require $base . '/vendor/autoload.php';
try {
SinceLeoo\Plugin\PluginBootstrap::init();
Hyperf\Di\ClassLoader::init();
$container = require $base . '/config/container.php';
$targets = [
 App\Application\Admin\Catalog\AppProductCommandService::class,
 App\Application\Admin\GroupBuy\AppGroupBuyLifecycleService::class,
 App\Infrastructure\Library\DataPermission\Aspects\DataScopeAspect::class,
 App\Domain\Infrastructure\Plugin\Contract\AnalyticsQueryInterface::class,
 App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface::class,
 App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface::class,
 App\Domain\Trade\Shared\Contract\TradeWebhookInterface::class,
 App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface::class,
 Plugin\CustomerService\Application\Worker\CustomerServiceWorker::class,
 Plugin\CustomerService\Socket\CustomerServiceSocketHandler::class,
 App\Application\Api\Payment\AppPaymentCallbackService::class,
 App\Application\Api\Order\AppTradeRecoveryService::class,
 App\Application\Api\Order\AppApiOrderCommandService::class,
 App\Application\Api\Order\AppOrderSubmissionService::class,
 App\Infrastructure\Crontab\OrderAutoConfirmCrontab::class,
 App\Interface\Api\Controller\V1\Member\MemberController::class,
];
if (!$disabled) {
    $targets[] = Plugin\Distribution\Interface\Admin\DistributionController::class;
    $targets[] = Plugin\Distribution\Interface\Api\DistributionController::class;
    $targets[] = Plugin\Distribution\Interface\Api\DistributionCapabilityController::class;
    $targets[] = Plugin\Distribution\Infrastructure\Listener\DistributionTradeListener::class;
    $targets[] = Plugin\Distribution\Application\Registration\AppDistributionRegistrationService::class;
    $targets[] = Plugin\Distribution\Infrastructure\Aspect\RegistrationAspect::class;
    $targets[] = Plugin\Distribution\Infrastructure\Aspect\RegistrationEventAspect::class;
    $targets[] = Plugin\Distribution\Infrastructure\Listener\MemberRegisteredListener::class;
    $targets[] = Plugin\Distribution\Infrastructure\Aspect\DistributionUninstallAspect::class;
}
$result = [];
foreach ($targets as $target) { $result[$target] = $container->get($target)::class; }
// The host constructors remain independent of the optional business plugin.
foreach ([App\Application\Api\Order\AppApiOrderCommandService::class, App\Application\Api\Order\AppOrderSubmissionService::class, App\Application\Api\Order\AppTradeRecoveryService::class] as $target) {
    if ((new ReflectionClass($target))->hasProperty('distribution')) {
        throw new RuntimeException('Host service still contains a distribution-specific dependency: ' . $target);
    }
}
$outbox = (new ReflectionProperty(App\Application\Api\Order\AppApiOrderCommandService::class, 'outbox'))->getValue($container->get(App\Application\Api\Order\AppApiOrderCommandService::class));
if (!$outbox instanceof App\Domain\Trade\Shared\Contract\TradeOutboxInterface) {
    throw new RuntimeException('Order completion outbox was not injected.');
}
$result['distribution_autoloadable'] = class_exists(Plugin\Distribution\ConfigProvider::class);
$result['analytics_autoloadable'] = class_exists(Plugin\Analytics\ConfigProvider::class);
echo json_encode($result, JSON_THROW_ON_ERROR) . PHP_EOL;
} finally {
    $remove = static function (string $path) use (&$remove): void {
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) as $item) { if ($item !== '.' && $item !== '..') $remove($path . '/' . $item); }
            rmdir($path);
        } else { unlink($path); }
    };
    $remove($base);
}

