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

// Only the disabled manifest and install lock below are physical, disposable test copies.
[$script, $socket, $database] = $argv;
if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/mysql.sock') || ! preg_match('/^codex_test_[a-z0-9_]+$/D', $database)) {
    throw new RuntimeException('Private test database required');
}
$source = dirname(__DIR__, 3);
$base = '/tmp/mine-shop-tests.diy-' . bin2hex(random_bytes(6));
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
symlink($source . '/plugins/diy', $base . '/plugins/diy');
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
    $previousUser = \App\Infrastructure\Model\Permission\User::findOrFail(2);
    $checks = 0;
    $check = static function ($expected, $actual) use (&$checks): void {
        ++$checks;
        if ($expected !== $actual) {
            throw new RuntimeException('Expected ' . json_encode($expected) . ', got ' . json_encode($actual));
        }
    };
    $pages = new \Plugin\Diy\Domain\Content\DiyPage\Repository\DiyPageRepository(new \Plugin\Diy\Infrastructure\Model\Content\DiyPage());
    $pageService = new \Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPageService($pages);
    $records = ($argv[3] ?? '') === 'run-only'
        ? new class (new \Plugin\Diy\Infrastructure\Model\Content\DiyPagePublishRecord()) extends \Plugin\Diy\Domain\Content\DiyPage\Repository\DiyPublishRecordRepository {
            public function dueScheduleIds(int $limit = 100): array
            {
                return [1];
            }
        }
    : new \Plugin\Diy\Domain\Content\DiyPage\Repository\DiyPublishRecordRepository(new \Plugin\Diy\Infrastructure\Model\Content\DiyPagePublishRecord());
    $domain = new \Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPublishService($records, $pageService);
    $commands = new \Plugin\Diy\Application\Admin\Content\AppDiyPublishCommandService($domain);
    $runner = new \Plugin\Diy\Application\Admin\Content\AppDiyScheduledPublishService($domain, new \Psr\Log\NullLogger());
    if (($argv[3] ?? '') === 'run-only') {
        echo json_encode(['published' => $runner->execute()], JSON_THROW_ON_ERROR) . PHP_EOL;
        return;
    }
    $schema = ['version' => 1, 'page' => ['key' => 'home', 'title' => 'Scheduled'], 'components' => []];
    for ($id = 1; $id <= 7; ++$id) {
        $connection->table('diy_pages')->insert(['id' => $id, 'page_key' => 'home', 'page_type' => 'all', 'title' => 'Page ' . $id, 'created_by' => $id === 2 ? 2 : 1]);
        $connection->table('diy_page_versions')->insert(['id' => $id, 'page_id' => $id, 'version_no' => 1, 'status' => 'draft', 'schema' => json_encode($schema)]);
    }
    \Hyperf\Context\Context::set('current_user', $user);
    \Hyperf\Context\Context::set('data_permission_enabled', true);
    $input = new \Plugin\Diy\Interface\Admin\Dto\DiyPage\DiyPublishScheduleDto(1);
    $input->version_id = 1;
    $input->scheduled_at = \Carbon\Carbon::now()->addHour()->toDateTimeString();
    $record = $commands->schedule($input, 1);
    try {
        $commands->schedule($input, 1);
        throw new RuntimeException('duplicate schedule accepted');
    } catch (DomainException) {
    }
    $input->page_id = 2;
    $input->version_id = 2;
    try {
        $commands->schedule($input, 1);
        throw new RuntimeException('foreign page accepted');
    } catch (DomainException) {
    }
    $input->page_id = 3;
    try {
        $commands->schedule($input, 1);
        throw new RuntimeException('foreign version accepted');
    } catch (DomainException) {
    }
    $draftInput = new \Plugin\Diy\Interface\Admin\Dto\DiyPage\DiyPageDraftDto();
    $draftInput->schema = $schema;
    $draftInput->schema['page']['title'] = 'New draft';
    $newDraft = Db::transaction(fn () => $pageService->saveDraft(1, $draftInput, 1));
    $check(false, $newDraft->id === 1);
    $check('Scheduled', $pages->findVersion(1, 1)->schema['page']['title']);
    $check(0, $runner->execute()); // Future record is not due.
    $check($user, \Hyperf\Context\Context::get('current_user'));
    // Legacy records and subsequent revocations must also be checked when executing.
    \Hyperf\Context\Context::destroy('current_user');
    \Hyperf\Context\Context::set('data_permission_enabled', false);
    $connection->table('diy_page_publish_records')->where('id', $record->id)->update(['scheduled_at' => \Carbon\Carbon::now()->subMinute()]);
    foreach ([2 => 1, 3 => 2, 4 => 3, 5 => 1, 6 => 1, 7 => 1] as $pageId => $operatorId) {
        $connection->table('diy_page_publish_records')->insert(['page_id' => $pageId, 'version_id' => $pageId, 'operator_id' => $operatorId, 'publish_type' => 'scheduled', 'publish_status' => 'pending', 'scheduled_at' => \Carbon\Carbon::now()->subMinute()]);
    }
    $connection->table('user')->where('id', 2)->update(['status' => 2]);
    $connection->table('user_belongs_role')->where('user_id', 3)->delete();
    $connection->table('diy_page_versions')->where('id', 6)->update(['schema' => '{"page":{"key":"wrong"},"components":[]}']);
    $connection->table('diy_pages')->where('id', 7)->update(['created_by' => 2]);
    \Hyperf\Context\Context::set('current_user', $user);
    \Hyperf\Context\Context::set('data_permission_enabled', true);
    $cancelId = (int) $connection->table('diy_page_publish_records')->where('page_id', 5)->value('id');
    try {
        $commands->cancelSchedule($cancelId, 4);
        throw new RuntimeException('wrong cancellation page accepted');
    } catch (DomainException) {
    }
    $check(true, $commands->cancelSchedule($cancelId, 5));
    \Hyperf\Context\Context::set('current_user', $previousUser);
    \Hyperf\Context\Context::set('data_permission_enabled', false);
    $check(1, $runner->execute());
    $check($previousUser, \Hyperf\Context\Context::get('current_user'));
    $check(false, \Hyperf\Context\Context::get('data_permission_enabled'));
    $check(0, $runner->execute());
    $check(1, (int) $connection->table('diy_page_publish_records')->where('publish_status', 'published')->count());
    $check(5, (int) $connection->table('diy_page_publish_records')->where('publish_status', 'failed')->count());
    $check(1, (int) $connection->table('diy_page_publish_records')->where('publish_status', 'cancelled')->count());
    $check(1, (int) $connection->table('diy_pages')->where('id', 1)->value('published_version_id'));
    $check('draft', $connection->table('diy_page_versions')->where('id', 6)->value('status'));
    $check(null, $connection->table('diy_pages')->where('id', 6)->value('published_version_id'));
    $check(true, str_contains((string) (new ReflectionMethod(\Hyperf\Database\Query\Builder::class, 'update'))->getFileName(), '/runtime/container/proxy/'));
    echo json_encode(['published' => 1, 'checks' => $checks], JSON_THROW_ON_ERROR) . PHP_EOL;
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
