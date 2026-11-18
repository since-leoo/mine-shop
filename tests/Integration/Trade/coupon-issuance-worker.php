<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap-unit.php';

use App\Application\Admin\Coupon\AppCouponUserCommandService;
use App\Application\Api\Coupon\Member\AppApiMemberCouponCommandService;
use App\Domain\Trade\Coupon\Api\Command\DomainApiCouponUserCommandService;
use App\Domain\Trade\Coupon\Repository\CouponRepository;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;
use App\Domain\Trade\Coupon\Service\DomainCouponService;
use App\Domain\Trade\Coupon\Service\DomainCouponUserService;
use App\Infrastructure\Model\Coupon\Coupon;
use App\Infrastructure\Model\Coupon\CouponUser;
use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolver;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Db;
use HyperfTests\Support\TestContainer;
use Psr\EventDispatcher\EventDispatcherInterface;

[$script, $database, $mode, $couponId, $memberId, $barrier] = $argv;
$socket = getenv('MINE_SHOP_TEST_MYSQL_SOCKET');
if (! preg_match('/^codex_test_[0-9]+_[a-f0-9]+$/', $database) || ! str_starts_with((string) $socket, '/tmp/mine-shop-tests.') || ! str_ends_with((string) $socket, '/mysql.sock')) {
    throw new RuntimeException('Only disposable test databases are allowed.');
}
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$connection = new MySqlConnection($pdo, $database, '', ['name' => 'default', 'driver' => 'mysql']);
$container = new TestContainer([
    ConnectionResolverInterface::class => new ConnectionResolver(['default' => $connection]),
    ConfigInterface::class => new Config(['databases' => ['default' => ['driver' => 'mysql', 'prefix' => '']]]),
    EventDispatcherInterface::class => new class implements EventDispatcherInterface { public function dispatch(object $event): object { return $event; } },
]);
$container->set(Db::class, new Db($container));
ApplicationContext::setContainer($container);

$coupons = new class(new Coupon()) extends CouponRepository {
    public function countIssued(int $couponId): int
    {
        $issued = parent::countIssued($couponId);
        // Widen the original count/insert race, without adding any lock in the test.
        usleep(200000);
        return $issued;
    }
};
$users = new CouponUserRepository(new CouponUser());
$domain = new DomainCouponService($coupons, $users);
$receive = new AppApiMemberCouponCommandService(new DomainApiCouponUserCommandService($domain, $coupons, $users));
$issue = new AppCouponUserCommandService(new DomainCouponUserService($coupons, $users, $domain));
fwrite(STDOUT, "READY\n");
$deadline = microtime(true) + 10;
while (! is_file($barrier)) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Race barrier timed out.');
    }
    usleep(1000);
    clearstatcache(true, $barrier);
}
try {
    $issued = $mode === 'receive'
        ? ($receive->receive((int) $memberId, (int) $couponId) ? 1 : 0)
        : count($issue->issue((int) $couponId, [(int) $memberId]));
    echo json_encode(['issued' => $issued], JSON_THROW_ON_ERROR);
} catch (DomainException $error) {
    if (! in_array($error->getMessage(), ['已达到领取上限', '优惠券库存不足'], true)) {
        throw $error;
    }
    echo json_encode(['issued' => 0, 'reason' => $error->getMessage()], JSON_THROW_ON_ERROR);
}
