<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap-unit.php';

use App\Application\Admin\Trade\AppOrderManualPaymentService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Member\Repository\MemberWalletRepository;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderCancellationService;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Model\Member\MemberWallet;
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderPayment;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;
use App\Infrastructure\Service\Pay\YsdPayService;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use App\Interface\Admin\Dto\Order\OrderManualPayDto;
use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolver;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Db;
use HyperfTests\Support\TestContainer;
use Psr\EventDispatcher\EventDispatcherInterface;

[$script, $database, $mode, $orderNo, $barrier] = $argv;
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
// No setting lookup is needed for balance payment/cancellation; no application bootstrap or .env is loaded.
$settings = (new ReflectionClass(DomainMallSettingService::class))->newInstanceWithoutConstructor();
$orders = new DomainOrderService(new OrderRepository(new Order()), $settings);
$paymentRepo = new OrderPaymentRepository(new OrderPayment());
$pay = new DomainPayService($orders, new DomainOrderPaymentService($paymentRepo), $paymentRepo,
    new DomainMemberWalletService(new MemberWalletRepository(new MemberWallet())),
    new MemberWalletTransactionRepository(new MemberWalletTransaction()), new PaymentPolicy(), new OrderPaymentRefundRepository(), new \App\Infrastructure\Plugin\NullExternalFulfillment());
$coupons = new class implements CouponServiceInterface {
    public function findUsableCoupon(int $memberId, int $couponId): ?array { return null; }
    public function settleCoupon(int $couponUserId, int $orderId): void {}
    public function releaseOrderCoupons(int $orderId): void {}
};
$cancel = new DomainOrderCancellationService($orders, $coupons, new YsdPayService(), $settings, new WechatPayConfigResolver());
$manual = new AppOrderManualPaymentService($pay, $orders, new HyperfTransactionManager(), new TradeOutboxRepository());
fwrite(STDOUT, "READY\n");
$deadline = microtime(true) + 10;
while (! is_file($barrier)) {
    if (microtime(true) > $deadline) { throw new RuntimeException('Race barrier timed out.'); }
    usleep(1000);
    clearstatcache(true, $barrier);
}
try {
    if ($mode === 'manual') {
        // 直接调用真实应用服务，不能让测试提前加锁掩盖认款链路自身的并发缺陷。
        $input = new OrderManualPayDto();
        $input->order_id = (int) $connection->table('orders')->where('order_no', $orderNo)->value('id');
        $input->operator_id = 42;
        $input->operator_name = '并发认款管理员';
        $input->remark = '已核实线下收款';
        $manual->pay($input);
    } else {
        (new HyperfTransactionManager())->run(function () use ($mode, $orderNo, $orders, $pay, $cancel): void {
            $orders->getLockedEntity($orderNo);
            usleep(100000); // Keep a real row lock long enough to force overlap with the other process.
            if ($mode === 'pay') {
                $pay->payByBalance($orderNo, 7, new PaymentSetting(false, [], true, 0, true, []));
            } elseif ($mode === 'cancel') {
                $cancel->cancel($orderNo, 7, false);
            } else {
                throw new RuntimeException('Unsupported worker mode.');
            }
        });
    }
    echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
} catch (DomainException $e) {
    echo json_encode(['ok' => false, 'reason' => $e->getMessage()], JSON_THROW_ON_ERROR);
}
