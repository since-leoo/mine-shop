<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Wechat;

use App\Domain\Auth\Service\DomainTokenService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Service\DomainMemberPointsService;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Interface\Api\Controller\V1\Member\MemberController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\ResultCode;
use EasyWeChat\MiniApp\Application;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\TestCase;
use Plugin\Wechat\ConfigProvider;
use Plugin\Wechat\Handler\LazyMiniAppHandler;
use Plugin\Wechat\Handler\MiniAppAppHandler;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class MiniAppLazyInitializationTest extends TestCase
{
    private ?ContainerInterface $previousContainer = null;

    protected function setUp(): void
    {
        $this->previousContainer = ApplicationContext::hasContainer() ? ApplicationContext::getContainer() : null;
    }

    protected function tearDown(): void
    {
        // ApplicationContext has no clear API; restore its pre-test nullable state without leaking mocks to other suites.
        (new \ReflectionProperty(ApplicationContext::class, 'container'))->setValue(null, $this->previousContainer);
    }

    public function testMemberCenterReadsProfileAndCountsWithoutReadingWechatConfiguration(): void
    {
        $container = $this->container();
        $settingsRead = [];
        $settings = $this->createMock(DomainSystemSettingService::class);
        $settings->method('get')->willReturnCallback(static function (string $key, mixed $default = null) use (&$settingsRead): mixed {
            $settingsRead[] = $key;
            self::assertNotSame('mall.integration.wechat_auth_config', $key);
            return $default;
        });
        $container->set(DomainSystemSettingService::class, $settings);

        $member = $this->createMock(MemberRepository::class);
        $member->expects(self::exactly(2))->method('detail')->with(42)->willReturn([
            'id' => 42, 'nickname' => '新注册会员', 'avatar' => '/test/member.png', 'phone' => '13800000000',
            'invite_code' => 'TEST0042', 'wallet' => ['balance' => 2350], 'points_wallet' => ['balance' => 100],
        ]);
        $member->expects(self::once())->method('referralCount')->with(42)->willReturn(3);
        $container->set(MemberRepository::class, $member);
        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::once())->method('countByMemberAndStatuses')->with(42)->willReturn([1, 2, 3, 4, 5]);
        $container->set(OrderRepository::class, $orders);
        $coupons = $this->createMock(CouponUserRepository::class);
        $coupons->expects(self::once())->method('countByMember')->with(42, 'unused')->willReturn(2);
        $container->set(CouponUserRepository::class, $coupons);

        $currentMember = $this->createMock(CurrentMember::class);
        $currentMember->method('id')->willReturn(42);
        $container->set(CurrentMember::class, $currentMember);
        // Unused token/sign-in infrastructure has no side effects in this read-only endpoint.
        $container->set(DomainTokenService::class, $this->createMock(DomainTokenService::class));
        $container->set(DomainMemberPointsService::class, $this->createMock(DomainMemberPointsService::class));
        $container->set(EventDispatcherInterface::class, $this->createMock(EventDispatcherInterface::class));
        $container->set(RequestInterface::class, $this->createMock(RequestInterface::class));

        // Real DI creates the controller, application/domain services, referral service, and both MiniApp consumers.
        $controller = $container->get(MemberController::class);
        self::assertSame([], $settingsRead);
        $result = $controller->center();
        self::assertSame(ResultCode::SUCCESS, $result->code);
        self::assertSame('新注册会员', $result->data['userInfo']['nickName']);
        self::assertSame('/test/member.png', $result->data['userInfo']['avatarUrl']);
        self::assertSame('TEST0042', $result->data['userInfo']['inviteCode']);
        self::assertSame([2350, 100, 2], array_column($result->data['countsData'], 'num'));
        self::assertSame([1, 2, 3, 4, 5], array_column($result->data['orderTagInfos'], 'orderNum'));
        self::assertSame(3, $result->data['referralCount']);
        self::assertSame('新注册会员', $controller->profile()->data['member']['nickname']);
        self::assertNotEmpty($settingsRead);
    }

    public function testMissingWechatConfigurationFailsOnlyOnActualWechatCall(): void
    {
        $container = $this->container();
        $settings = $this->createMock(DomainSystemSettingService::class);
        $settings->expects(self::once())->method('get')->with('mall.integration.wechat_auth_config', self::isType('array'))->willReturn([]);
        $container->set(DomainSystemSettingService::class, $settings);
        $miniApp = $container->get(MiniAppInterface::class);
        self::assertInstanceOf(MiniAppInterface::class, $miniApp);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('请先在后台“系统集成”中配置微信授权 AppID 和 AppSecret');
        $miniApp->silentAuthorize('fixture-login-code');
    }

    public function testConfigurationCanBeAddedAfterFailureAndExistingSdkPerformsLogin(): void
    {
        $requests = [];
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'query' => $options['query']];
            return new MockResponse('{"openid":"fixture-openid","session_key":"fixture-session"}', ['http_code' => 200]);
        }, 'https://api.weixin.qq.com');
        $created = 0;
        $sdk = null;
        $container = $this->container([
            Application::class => static function (ContainerInterface $container, array $parameters) use ($http, &$created, &$sdk): Application {
                ++$created;
                $sdk = new Application($parameters[0]);
                return $sdk->setHttpClient($http);
            },
        ]);
        $settingsValue = [];
        $settings = $this->createMock(DomainSystemSettingService::class);
        // Capture by reference to simulate a merchant filling in settings after a failed WeChat operation.
        $settings->expects(self::exactly(2))->method('get')->willReturnCallback(static function () use (&$settingsValue): array { return $settingsValue; });
        $container->set(DomainSystemSettingService::class, $settings);
        $request = $this->createMock(RequestInterface::class);
        $cache = $this->createMock(CacheInterface::class);
        $container->set(RequestInterface::class, $request);
        $container->set(CacheInterface::class, $cache);

        $miniApp = $container->get(MiniAppInterface::class);
        self::assertSame(0, $created);
        try {
            $miniApp->silentAuthorize('before-config');
            self::fail('A missing AppID/AppSecret must still fail explicitly.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('AppID 和 AppSecret', $e->getMessage());
        }
        self::assertSame([], $requests);
        self::assertSame(0, $created);

        $settingsValue = ['app_id' => 'wx-fixture-only', 'secret' => 'fixture-secret-only'];
        $expected = ['openid' => 'fixture-openid', 'session_key' => 'fixture-session'];
        self::assertSame($expected, $miniApp->silentAuthorize('first-code'));
        self::assertSame($expected, $miniApp->silentAuthorize('second-code'));
        self::assertSame(1, $created);
        self::assertSame($request, $sdk->getRequest());
        self::assertSame($cache, $sdk->getCache());
        self::assertSame($sdk->getClient(), $miniApp->getClient());
        self::assertSame('wx-fixture-only', $miniApp->customSdkCall(static fn (Application $app): string => $app->getAccount()->getAppId()));
        self::assertSame(['first-code', 'second-code'], array_column(array_column($requests, 'query'), 'js_code'));
        foreach ($requests as $call) {
            self::assertSame('GET', $call['method']);
            self::assertSame('/sns/jscode2session', parse_url($call['url'], PHP_URL_PATH));
            self::assertSame('wx-fixture-only', $call['query']['appid']);
            self::assertSame('fixture-secret-only', $call['query']['secret']);
            self::assertSame('authorization_code', $call['query']['grant_type']);
        }
    }

    public function testProxyPreservesEveryOperationAndTheExistingHandlersDefaults(): void
    {
        $calls = [
            'sendSubscribeMessage' => ['openid', 'template', ['thing1' => ['value' => '通知']], 'pages/order'],
            'getLimitedWxaCode' => ['pages/home', 'invite=42', true, ['width' => 280]],
            'getSchemeCode' => ['pages/home', 'invite=42', 'trial', 123, 1, 7],
            'checkSchemeCode' => ['weixin://fixture', 1],
            'getMiniShortLink' => ['pages/home?a=1', '商城', true],
            'performSilentLogin' => ['code', 'encrypted-data', 'iv'],
            'getPhoneNumber' => ['phone-code'],
            'silentAuthorize' => ['auth-code'],
            'getUrlLink' => ['pages/home', 'a=1', 1, 123, 7, ['env' => 'test']],
            'checkUrlLink' => ['https://wxaurl.cn/fixture', 1],
        ];
        $target = $this->createMock(MiniAppInterface::class);
        foreach ($calls as $method => $arguments) {
            $target->expects(self::once())->method($method)->with(...$arguments)->willReturn(['operation' => $method]);
            $existing = (new \ReflectionMethod(MiniAppAppHandler::class, $method))->getParameters();
            $lazy = (new \ReflectionMethod(LazyMiniAppHandler::class, $method))->getParameters();
            foreach ($existing as $index => $parameter) {
                if ($parameter->isDefaultValueAvailable()) {
                    self::assertSame($parameter->getDefaultValue(), $lazy[$index]->getDefaultValue(), $method . ' default');
                }
            }
        }
        $created = 0;
        $miniApp = new LazyMiniAppHandler(static function () use ($target, &$created): MiniAppInterface {
            ++$created;
            return $target;
        });
        self::assertSame(0, $created);
        foreach ($calls as $method => $arguments) {
            self::assertSame(['operation' => $method], $miniApp->{$method}(...$arguments));
        }
        self::assertSame(1, $created);
    }

    private function container(array $definitions = []): Container
    {
        $container = new Container(new DefinitionSource(array_replace((new ConfigProvider())()['dependencies'], $definitions)));
        ApplicationContext::setContainer($container);
        return $container;
    }
}
