<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Sms\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Exception\System\BusinessException;
use PHPUnit\Framework\TestCase;
use Plugin\Sms\Service\EasySmsVerificationService;
use Plugin\Sms\Service\SmsSenderInterface;

/**
 * @internal
 * @coversNothing
 */
final class EasySmsVerificationServiceTest extends TestCase
{
    private ?string $previousAppEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousAppEnv = getenv('APP_ENV') === false ? null : (string) getenv('APP_ENV');
    }

    protected function tearDown(): void
    {
        if ($this->previousAppEnv === null) {
            putenv('APP_ENV');
            $_ENV['APP_ENV'] = null;
            $_SERVER['APP_ENV'] = null;
        } else {
            putenv('APP_ENV=' . $this->previousAppEnv);
            $_ENV['APP_ENV'] = $this->previousAppEnv;
            $_SERVER['APP_ENV'] = $this->previousAppEnv;
        }

        parent::tearDown();
    }

    public function testProductionRejectsWhenSmsChannelDisabledAndDoesNotStoreCode(): void
    {
        $cache = new InMemorySmsCache();
        $service = new EasySmsVerificationService(
            $this->mallSettings(new IntegrationSetting(
                'aliyun',
                ['template_code' => 'SMS_1'],
                ['sms' => false],
                'Your code is {$code}',
                '',
                '',
                false,
            )),
            $cache,
        );

        $this->useProductionEnvironment();

        $this->expectException(BusinessException::class);

        try {
            $service->sendCode('13800138000', 'register');
        } finally {
            self::assertSame([], $cache->all());
        }
    }

    public function testProductionRejectsWhenSmsProviderDisabledAndDoesNotStoreCode(): void
    {
        $cache = new InMemorySmsCache();
        $service = new EasySmsVerificationService(
            $this->mallSettings(new IntegrationSetting(
                'disabled',
                ['template_code' => 'SMS_1'],
                ['sms' => true],
                'Your code is {$code}',
                '',
                '',
                false,
            )),
            $cache,
        );

        $this->useProductionEnvironment();

        $this->expectException(BusinessException::class);

        try {
            $service->sendCode('13800138000', 'register');
        } finally {
            self::assertSame([], $cache->all());
        }
    }

    public function testProductionDisabledSmsErrorTakesPrecedenceOverExistingRateLimit(): void
    {
        $cache = new InMemorySmsCache();
        $cache->setPrefix('/plugin/sms/verification');
        $cache->set('rate:register:13800138000', 'existing');

        $service = new EasySmsVerificationService(
            $this->mallSettings(new IntegrationSetting(
                'disabled',
                ['template_code' => 'SMS_1'],
                ['sms' => true],
                'Your code is {$code}',
                '',
                '',
                false,
            )),
            $cache,
        );

        $this->useProductionEnvironment();

        try {
            $service->sendCode('13800138000', 'register');
            self::fail('Expected SMS disabled business exception.');
        } catch (BusinessException $exception) {
            self::assertSame('SMS service is disabled.', $exception->getResponse()->message);
        }
    }

    public function testProductionSendsWithConfiguredProviderConfigAndTemplate(): void
    {
        $sender = new CapturingSmsSender();
        $service = new EasySmsVerificationService(
            $this->mallSettings(new IntegrationSetting(
                'tencent',
                [
                    'sdk_app_id' => 'sdk-1',
                    'secret_id' => 'secret-id-1',
                    'secret_key' => 'secret-key-1',
                    'sign_name' => 'MineShop',
                    'template_id' => 'TPL_100',
                ],
                ['sms' => true],
                'Verification code: {$code}',
                '',
                '',
                false,
            )),
            new InMemorySmsCache(),
            $sender,
        );

        $this->useProductionEnvironment();

        $result = $service->sendCode('13800138000', 'forgot_password');

        self::assertArrayNotHasKey('code', $result);
        self::assertSame('13800138000', $sender->phone);
        self::assertSame('tencent', $sender->config['default']['gateways'][0]);
        self::assertSame('sdk-1', $sender->config['gateways']['tencent']['sdk_app_id']);
        self::assertSame('secret-id-1', $sender->config['gateways']['tencent']['secret_id']);
        self::assertSame('secret-key-1', $sender->config['gateways']['tencent']['secret_key']);
        self::assertSame('MineShop', $sender->config['gateways']['tencent']['sign_name']);
        self::assertSame('TPL_100', $sender->payload['template']);
        self::assertMatchesRegularExpression('/^Verification code: \d{6}$/', $sender->payload['content']);
        self::assertMatchesRegularExpression('/^\d{6}$/', $sender->payload['data']['code']);
    }

    public function testNonProductionRejectsResendWithinInterval(): void
    {
        $service = new EasySmsVerificationService(
            $this->mallSettings($this->enabledAliyunIntegration()),
            new InMemorySmsCache(),
        );

        $this->useLocalEnvironment();
        $service->sendCode('13800138000', 'register');

        $this->expectException(BusinessException::class);

        $service->sendCode('13800138000', 'register');
    }

    public function testNonProductionRejectsAfterDailyLimit(): void
    {
        $service = new EasySmsVerificationService(
            $this->mallSettings($this->enabledAliyunIntegration()),
            new InMemorySmsCache(),
        );

        $this->useLocalEnvironment();

        for ($i = 0; $i < 10; ++$i) {
            $service->sendCode('13800138000', 'scene_' . $i);
        }

        $this->expectException(BusinessException::class);

        $service->sendCode('13800138000', 'scene_10');
    }

    private function useProductionEnvironment(): void
    {
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
    }

    private function useLocalEnvironment(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
    }

    private function enabledAliyunIntegration(): IntegrationSetting
    {
        return new IntegrationSetting(
            'aliyun',
            [
                'access_key_id' => 'ak',
                'access_key_secret' => 'sk',
                'sign_name' => 'MineShop',
                'template_code' => 'SMS_1',
            ],
            ['sms' => true],
            'Your code is {$code}',
            '',
            '',
            false,
        );
    }

    private function mallSettings(IntegrationSetting $integration): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn($integration);

        return $settings;
    }
}

final class CapturingSmsSender implements SmsSenderInterface
{
    public ?string $phone = null;

    /**
     * @var array<string, mixed>
     */
    public array $payload = [];

    /**
     * @var array<string, mixed>
     */
    public array $config = [];

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $config
     */
    public function send(string $phone, array $payload, array $config): void
    {
        $this->phone = $phone;
        $this->payload = $payload;
        $this->config = $config;
    }
}

final class InMemorySmsCache extends ICache
{
    private string $prefix = '';

    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function __construct() {}

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function get(string $key): mixed
    {
        return $this->values[$this->key($key)] ?? null;
    }

    public function set(string $key, mixed $value, mixed $options = []): bool
    {
        $this->values[$this->key($key)] = $value;

        return true;
    }

    public function delete(string ...$key): bool
    {
        foreach ($key as $item) {
            unset($this->values[$this->key($item)]);
        }

        return true;
    }

    public function clear(string $prefix = ''): bool
    {
        $this->values = [];

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    private function key(string $key): string
    {
        return $this->prefix . ':' . $key;
    }
}
