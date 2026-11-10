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
use App\Application\Api\Auth\AppApiAuthCommandService;
use App\Application\Api\Member\AppApiMemberAuthCommandService;
use App\Domain\Auth\Service\DomainTokenService;
use App\Domain\Member\Event\MemberRegistered;
use App\Infrastructure\Library\DataPermission\Aspects\DataScopeAspect;
use App\Interface\Api\DTO\Auth\RegisterDto;
use DG\BypassFinals;
use Hyperf\Database\ConnectionResolver;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\ClassLoader;
use Hyperf\Event\EventDispatcher;
use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\TestCase;
use Plugin\Distribution\ConfigProvider;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Sms\Contract\SmsVerificationServiceInterface;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;
use SinceLeoo\Plugin\Contract\PluginManagerInterface;
use SinceLeoo\Plugin\PluginBootstrap;

// Fresh process: exercise production annotation scanning/proxy generation without a real .env.
$source = dirname(__DIR__, 3);
[$script, $socket, $database, $mode] = $argv;
if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/mysql.sock') || ! preg_match('/^codex_test_[a-z0-9_]+$/', $database)) {
    throw new RuntimeException('Private test database required.');
}
$base = '/tmp/mine-shop-tests.registration-aop.' . bin2hex(random_bytes(6));
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
// Uninstall tests use physical copies exclusively: no target file may point at the real plugin.
$copyPlugin = static function (string $from, string $to) use (&$copyPlugin): void {
    if (is_link($from)) {
        throw new RuntimeException('Refusing linked uninstall fixture content.');
    }
    if (is_dir($from)) {
        mkdir($to);
        foreach (scandir($from) as $name) {
            if ($name !== '.' && $name !== '..') {
                $copyPlugin($from . '/' . $name, $to . '/' . $name);
            }
        }
    } elseif (! copy($from, $to)) {
        throw new RuntimeException('Unable to copy isolated uninstall fixture.');
    }
};
foreach (glob($source . '/plugins/*/plugin.json') as $manifest) {
    $name = basename(dirname($manifest));
    $target = $base . '/plugins/' . $name;
    mkdir($target);
    $definition = json_decode(file_get_contents($manifest), true, 512, \JSON_THROW_ON_ERROR);
    if ($mode === 'disabled' && $name === 'distribution') {
        $definition['enabled'] = false;
    }
    file_put_contents($target . '/plugin.json', json_encode($definition, \JSON_THROW_ON_ERROR));
    foreach (glob(dirname($manifest) . '/*') as $item) {
        if (basename($item) === 'plugin.json') {
            continue;
        }
        if ($mode === 'uninstall-guard' && $name === 'distribution') {
            if (basename($item) !== 'install.lock') {
                $copyPlugin($item, $target . '/' . basename($item));
            }
        } else {
            symlink($item, $target . '/' . basename($item));
        }
    }
    if ($mode === 'uninstall-guard' && $name === 'distribution') {
        file_put_contents($target . '/install.lock', json_encode(['fixture' => 'isolated-uninstall', 'nonce' => bin2hex(random_bytes(12))], \JSON_THROW_ON_ERROR));
    }
}
file_put_contents($base . '/config/autoload/databases.php', '<?php return ' . var_export(['default' => ['driver' => 'mysql', 'unix_socket' => $socket, 'database' => $database, 'username' => 'root', 'password' => '', 'prefix' => '']], true) . ';');
file_put_contents($base . '/config/autoload/redis.php', '<?php return ["default" => ["host"=>"/tmp/mine-shop-tests.invalid/redis.sock", "port"=>0, "db"=>0]];');
require $base . '/vendor/autoload.php';
BypassFinals::enable();
try {
    PluginBootstrap::init();
    ClassLoader::init();
    $container = require $base . '/config/container.php';
    $pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection = new MySqlConnection($pdo, $database, '', ['name' => 'default', 'driver' => 'mysql']);
    $resolver = new ConnectionResolver(['default' => $connection]);
    $container->set(ConnectionResolverInterface::class, $resolver);
    $container->set(Db::class, new Db($container));
    $fixtures = new class('testFixture') extends TestCase {
        public function testFixture(): void {}

        public function double(string $class): object
        {
            return $this->createMock($class);
        }
    };
    // API fixture has no administrator data-scope context; avoid unrelated department/Redis infrastructure.
    $dataScope = $fixtures->double(DataScopeAspect::class);
    $dataScope->method('process')->willReturnCallback(static fn (ProceedingJoinPoint $join) => $join->process());
    $container->set(DataScopeAspect::class, $dataScope);
    $sms = $fixtures->double(SmsVerificationServiceInterface::class);
    $sms->method('verifyCode')->willReturn(true);
    $container->set(SmsVerificationServiceInterface::class, $sms);
    $wechat = $fixtures->double(MiniAppInterface::class);
    $wechat->method('silentAuthorize')->willReturnCallback(static fn (string $code) => ['openid' => 'openid-' . $code]);
    $container->set(MiniAppInterface::class, $wechat);
    $tokens = $fixtures->double(DomainTokenService::class);
    $tokens->method('using')->willReturnSelf();
    $tokens->method('buildAccessToken')->willReturn('fixture-token');
    $tokens->method('buildRefreshToken')->willReturn('fixture-refresh');
    $tokens->method('getTtl')->willReturn(7200);
    $container->set(DomainTokenService::class, $tokens);
    $raw = ['invite_code' => 'PARENT01'];
    $request = $fixtures->double(RequestInterface::class);
    $request->method('input')->willReturnCallback(static function (string $key) use (&$raw) { return $raw[$key] ?? null; });
    $container->set(RequestInterface::class, $request);
    $seen = [];
    $observe = static function (object $event) use (&$seen, $connection): void {
        if ($event instanceof MemberRegistered) {
            if ($connection->transactionLevel() !== 0) {
                throw new RuntimeException('Public registration event escaped before commit.');
            }
            $seen[] = ['member_id' => $event->memberId, 'source' => $event->source, 'referrer_id' => $event->referrerId];
        }
    };
    $provider = new class($observe) implements ListenerProviderInterface {
        public function __construct(private Closure $observer) {}

        public function getListenersForEvent(object $event): iterable
        {
            yield $this->observer;
        }
    };
    // This is the real framework EventDispatcher, whose dispatch method must be AOP-proxied.
    $container->set(EventDispatcherInterface::class, new EventDispatcher($provider));
    if ($mode === 'failure') {
        $domain = $fixtures->double(DomainDistributionService::class);
        $domain->method('bindNewMember')->willThrowException(new RuntimeException('injected plugin binding failure'));
        $container->set(DomainDistributionService::class, $domain);
    }
    if ($mode === 'uninstall-guard') {
        $discoverer = $container->get(PluginDiscovererInterface::class);
        $pluginPath = $discoverer->getPluginPath('since/distribution');
        $expectedPath = realpath($base . '/plugins/distribution');
        if (! is_string($pluginPath) || realpath($pluginPath) !== $expectedPath || $expectedPath === realpath($source . '/plugins/distribution')) {
            throw new RuntimeException('Uninstall must resolve only to the copied temporary plugin.');
        }
        $lock = $pluginPath . '/install.lock';
        if (is_link($pluginPath) || is_link($lock) || ! is_file($lock) || ! str_starts_with((string) realpath($lock), $expectedPath . '/')) {
            throw new RuntimeException('Uninstall lock must be a physical file inside the isolated plugin.');
        }
        $before = file_get_contents($lock);
        $manager = $container->get(PluginManagerInterface::class);
        $method = new ReflectionMethod($manager, 'uninstall');
        $result = ['manager_class' => $manager::class, 'proxied_method' => str_contains((string) $method->getFileName(), '/runtime/container/proxy/'), 'isolated_physical_plugin' => true];
        try {
            $result['returned'] = $manager->uninstall('since/distribution', true);
        } catch (DomainException $error) {
            $result['exception_class'] = $error::class;
            $result['message'] = $error->getMessage();
        }
        $result['lock_exists'] = is_file($lock) && ! is_link($lock);
        $result['lock_unchanged'] = $result['lock_exists'] && file_get_contents($lock) === $before;
        echo json_encode($result, \JSON_THROW_ON_ERROR) . \PHP_EOL;
        return;
    }
    $h5 = $container->get(AppApiAuthCommandService::class);
    $input = new RegisterDto();
    $input->phone = '13800138000';
    $input->password = 'fixture-password';
    $input->code = '123456';
    $result = [];
    try {
        $result['h5'] = $h5->register($input);
    } catch (RuntimeException $error) {
        if ($mode !== 'failure') {
            throw $error;
        } $result['error'] = $error->getMessage();
    }
    if ($mode === 'enabled') {
        $raw = ['share_token' => str_repeat('a', 24)];
        $mini = $container->get(AppApiMemberAuthCommandService::class);
        for ($i = 0; $i < 3; ++$i) {
            $result['mini'][] = $mini->miniProgramLogin('code-' . $i, openid: 'victim-openid', inviteCode: 'LEGACY99');
        }
        $raw = ['invite_code' => 'OTHER001'];
        $result['existing'] = $mini->miniProgramLogin('code-0', inviteCode: 'OTHER001');
    }
    $result['events'] = $seen;
    $result['member_count'] = $connection->table('members')->count();
    $result['bindings'] = $connection->table('distribution_bindings')->orderBy('member_id')->get()->all();
    $result['plugin_autoloadable'] = class_exists(ConfigProvider::class);
    echo json_encode($result, \JSON_THROW_ON_ERROR) . \PHP_EOL;
} finally {
    $remove = static function (string $path) use (&$remove): void {
        if (is_dir($path) && ! is_link($path)) {
            foreach (scandir($path) as $name) {
                if ($name !== '.' && $name !== '..') {
                    $remove($path . '/' . $name);
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    };
    $remove($base);
}
