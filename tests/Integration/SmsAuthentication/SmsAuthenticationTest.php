<?php

declare(strict_types=1);

namespace HyperfTests\Integration\SmsAuthentication;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use App\Domain\Member\Api\Command\DomainApiMemberPasswordAuthService;
use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Model\Member\Member;
use App\Interface\Api\DTO\Auth\ForgotPasswordDto;
use App\Interface\Api\DTO\Auth\H5LoginDto;
use App\Interface\Api\DTO\Auth\H5SmsLoginDto;
use App\Interface\Api\DTO\Auth\RegisterDto;
use HyperfTests\Support\DatabaseTestCase;
use HyperfTests\Support\IsolatedRedis;
use Plugin\Sms\Model\SmsMessage;
use Plugin\Sms\Service\EasySmsVerificationService;
use Plugin\Sms\Service\SmsSenderInterface;

final class SmsAuthenticationTest extends DatabaseTestCase
{
    use IsolatedRedis;

    private string|false $oldEnvironment;
    private const PREFIX = '/plugin/sms/verification:';

    protected function setUp(): void
    {
        parent::setUp();
        $this->nativeRedis = $this->openRedis();
        $this->oldEnvironment = getenv('APP_ENV');
        putenv('APP_ENV=testing');
        require_once BASE_PATH . '/migrations/2024_01_01_000006_create_mall_members_table.php';
        (new \CreateMallMembersTable())->up();
        $this->migrate('migrations/2026_03_20_000003_add_password_to_members_table.php');
        require_once BASE_PATH . '/migrations/2026_02_21_000001_add_member_referral_fields.php';
        (new \AddMemberReferralFields())->up();
    }

    protected function tearDown(): void
    {
        if (isset($this->oldEnvironment)) {
            putenv($this->oldEnvironment === false ? 'APP_ENV' : 'APP_ENV=' . $this->oldEnvironment);
        }
        $this->cleanRedis();
        parent::tearDown();
    }

    public function testActiveMemberLoginConsumesCodeAndPersistsLoginMetadata(): void
    {
        $id = $this->member('13800138000');
        $sms = $this->service();
        $code = $sms->sendCode('13800138000', 'login')['code'];
        $input = $this->loginInput('13800138000', $code);
        $member = $this->domain($sms)->loginBySms($input);
        self::assertSame($id, $member->getId());
        $stored = $this->connection->table('members')->where('id', $id)->first();
        self::assertSame('203.0.113.4', $stored->last_login_ip);
        self::assertNotNull($stored->last_login_at);
        self::assertSame(1, $this->connection->table('members')->count());
        self::assertFalse($sms->verifyCode($input->phone, 'login', $code));
    }

    public function testSmsLoginDoesNotRegisterOrActivateMembers(): void
    {
        $sms = $this->service();
        foreach (['missing', 'inactive', 'banned'] as $index => $status) {
            $phone = '1380013810' . $index;
            if ($status !== 'missing') $this->member($phone, $status);
            $code = $sms->sendCode($phone, 'login')['code'];
            try {
                $this->domain($sms)->loginBySms($this->loginInput($phone, $code));
                self::fail('Unavailable member must not log in.');
            } catch (\App\Infrastructure\Exception\System\BusinessException|\DomainException $error) {
                $message = $error instanceof \App\Infrastructure\Exception\System\BusinessException ? $error->getResponse()->toArray()['message'] : $error->getMessage();
                self::assertStringContainsString($status === 'missing' ? '会员不存在' : '停用', $message);
            }
        }
        self::assertSame(2, $this->connection->table('members')->count());
        self::assertSame(0, $this->connection->table('members')->whereNotNull('last_login_at')->count());
    }

    public function testRegisterAndPasswordResetKeepTheirOwnOneUseScenes(): void
    {
        $sms = $this->service();
        // Existing password-less H5 member exercises registration without inventing a WeChat identity.
        $id = $this->member('13800138001');
        $register = new RegisterDto();
        $register->phone = '13800138001'; $register->password = 'original-password';
        $register->code = $sms->sendCode($register->phone, 'register')['code'];
        self::assertFalse($sms->verifyCode($register->phone, 'login', $register->code));
        self::assertSame($id, $this->domain($sms)->register($register)->getId());
        self::assertFalse($sms->verifyCode($register->phone, 'register', $register->code));
        $reset = new ForgotPasswordDto();
        $reset->phone = $register->phone; $reset->password = 'changed-password';
        $reset->code = $sms->sendCode($reset->phone, 'forgot_password')['code'];
        self::assertFalse($sms->verifyCode($reset->phone, 'login', $reset->code));
        $this->domain($sms)->resetPassword($reset);
        self::assertTrue(password_verify($reset->password, $this->connection->table('members')->where('id', $id)->value('password')));
        self::assertFalse($sms->verifyCode($reset->phone, 'forgot_password', $reset->code));
    }

    public function testFreshH5MembersRegisterWithoutWechatAndCompleteBothLoginMethodsAndReset(): void
    {
        $sms = $this->service(); $domain = $this->domain($sms);
        foreach (['13800139001', '13800139002'] as $phone) {
            $register = new RegisterDto();
            $register->phone = $phone; $register->password = 'original-password';
            $register->code = $sms->sendCode($phone, 'register')['code'];
            $member = $domain->register($register);
            self::assertGreaterThan(0, $member->getId());
            self::assertNull($this->connection->table('members')->where('id', $member->getId())->value('openid'));
            $password = new H5LoginDto();
            $password->phone = $phone; $password->password = $register->password; $password->ip = '203.0.113.5';
            self::assertSame($member->getId(), $domain->loginByPassword($password)->getId());
            $smsCode = $sms->sendCode($phone, 'login')['code'];
            self::assertSame($member->getId(), $domain->loginBySms($this->loginInput($phone, $smsCode))->getId());
            $reset = new ForgotPasswordDto();
            $reset->phone = $phone; $reset->password = 'changed-password';
            $reset->code = $sms->sendCode($phone, 'forgot_password')['code'];
            $domain->resetPassword($reset);
            $password->password = $reset->password;
            self::assertSame($member->getId(), $domain->loginByPassword($password)->getId());
            self::assertFalse(password_verify($register->password, $this->connection->table('members')->where('id', $member->getId())->value('password')));
        }
        self::assertSame(2, $this->connection->table('members')->whereNull('openid')->count());
        $uniqueIndexes = array_filter($this->connection->select('SHOW INDEX FROM members'), static fn ($index) => $index->Column_name === 'openid' && (int) $index->Non_unique === 0);
        self::assertNotEmpty($uniqueIndexes, 'WeChat OpenID must retain its unique constraint.');
    }

    public function testFiveWrongAttemptsInvalidateTheCodeAndResendsAreRateLimited(): void
    {
        $sms = $this->service(); $phone = '13800138002';
        $code = $sms->sendCode($phone, 'login')['code'];
        for ($index = 0; $index < 5; ++$index) self::assertFalse($sms->verifyCode($phone, 'login', $code === '000000' ? '999999' : '000000'));
        self::assertFalse($sms->verifyCode($phone, 'login', $code));
        $this->expectException(\DomainException::class);
        $sms->sendCode($phone, 'login');
    }

    public function testDailyLimitIsSharedAcrossScenes(): void
    {
        $sms = $this->service(); $phone = '13800138003';
        for ($index = 0; $index < 10; ++$index) {
            $scene = ['register', 'forgot_password', 'login'][$index % 3];
            $this->nativeRedis->del(self::PREFIX . "rate:{$scene}:{$phone}");
            self::assertMatchesRegularExpression('/^\d{6}$/', $sms->sendCode($phone, $scene)['code']);
        }
        $this->nativeRedis->del(self::PREFIX . "rate:login:{$phone}");
        $this->expectException(\DomainException::class);
        $sms->sendCode($phone, 'login');
    }

    public function testProviderFailureDoesNotPublishCodeAndReleasesOnlyItsReservation(): void
    {
        putenv('APP_ENV=production');
        $phone = '13800138004'; $attempt = 0; $codes = [];
        $sms = $this->service(static function (SmsMessage $message) use (&$attempt, &$codes): void {
            $codes[] = $message->variables()['code'];
            if (++$attempt === 1) throw new \RuntimeException('provider rejected');
        });
        try { $sms->sendCode($phone, 'login'); self::fail('Provider failure must propagate.'); }
        catch (\RuntimeException $error) { self::assertSame('provider rejected', $error->getMessage()); }
        self::assertFalse($sms->verifyCode($phone, 'login', $codes[0]));
        self::assertSame('0', $this->nativeRedis->get(self::PREFIX . 'daily:' . date('Ymd') . ':' . $phone));
        self::assertArrayNotHasKey('code', $sms->sendCode($phone, 'login'));
        self::assertTrue($sms->verifyCode($phone, 'login', $codes[1]));
    }

    public function testDelayedFailedSendCannotDeleteTheNextSendCodeOrRateLock(): void
    {
        putenv('APP_ENV=production');
        $phone = '13800138005'; $laterCode = ''; $sms = null; $attempt = 0;
        $sms = $this->service(function (SmsMessage $message) use (&$attempt, &$sms, &$laterCode, $phone): void {
            if (++$attempt === 1) {
                // Simulate the first provider response taking longer than the resend interval.
                $this->nativeRedis->del(self::PREFIX . "rate:login:{$phone}");
                $sms->sendCode($phone, 'login');
                throw new \RuntimeException('first provider request failed late');
            }
            $laterCode = $message->variables()['code'];
        });
        try { $sms->sendCode($phone, 'login'); self::fail('Late failure must propagate.'); }
        catch (\RuntimeException $error) { self::assertSame('first provider request failed late', $error->getMessage()); }
        self::assertTrue($sms->verifyCode($phone, 'login', $laterCode));
        self::assertSame('1', $this->nativeRedis->get(self::PREFIX . 'daily:' . date('Ymd') . ':' . $phone));
        self::assertGreaterThan(0, $this->nativeRedis->ttl(self::PREFIX . "rate:login:{$phone}"));
    }

    public function testDelayedSuccessCannotOverwriteANewerDeliveredCode(): void
    {
        putenv('APP_ENV=production');
        $phone = '13800138008'; $codes = []; $sms = null;
        $sms = $this->service(function (SmsMessage $message) use (&$sms, &$codes, $phone): void {
            $codes[] = $message->variables()['code'];
            if (count($codes) === 1) {
                $this->nativeRedis->del(self::PREFIX . "rate:login:{$phone}");
                $sms->sendCode($phone, 'login');
            }
        });
        try { $sms->sendCode($phone, 'login'); self::fail('Old response cannot publish.'); }
        catch (\DomainException $error) { self::assertStringContainsString('最新验证码', $error->getMessage()); }
        self::assertSame($codes[1], $this->nativeRedis->get(self::PREFIX . "code:login:{$phone}"));
        self::assertTrue($sms->verifyCode($phone, 'login', $codes[1]));
    }

    private function service(?\Closure $send = null): EasySmsVerificationService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting('aliyun', ['template_code' => 'test'], ['sms' => true], 'code: {$code}', '', '', false));
        $cache = $this->createMock(ICache::class);
        $cache->method('setPrefix')->willReturnSelf();
        $cache->method('getPrefix')->willReturn('/plugin/sms/verification');
        $cache->method('eval')->willReturnCallback(fn (string $script, array $args, int $keys): bool => (bool) $this->nativeRedis->eval($script, $args, $keys));
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->method('send')->willReturnCallback($send ?? static function (): void { throw new \RuntimeException('No real SMS provider is allowed in tests.'); });
        return new EasySmsVerificationService($settings, $cache, $sender);
    }

    private function member(string $phone, string $status = 'active'): int
    {
        return $this->connection->table('members')->insertGetId(['openid' => 'test-' . $phone, 'phone' => $phone, 'nickname' => '测试会员', 'status' => $status, 'source' => 'h5']);
    }

    private function domain(EasySmsVerificationService $sms): DomainApiMemberPasswordAuthService
    {
        return new DomainApiMemberPasswordAuthService(new MemberRepository(new Member()), $sms);
    }

    private function loginInput(string $phone, string $code): H5SmsLoginDto
    {
        $input = new H5SmsLoginDto(); $input->phone = $phone; $input->code = $code; $input->ip = '203.0.113.4';
        return $input;
    }
}
