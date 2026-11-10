<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Auth;

use App\Application\Api\Auth\AppApiAuthCommandService;
use App\Domain\Auth\Service\DomainTokenService;
use App\Domain\Member\Api\Command\DomainApiMemberPasswordAuthService;
use App\Domain\Member\Entity\MemberEntity;
use App\Interface\Api\DTO\Auth\H5SmsLoginDto;
use App\Interface\Api\Request\V1\H5SmsLoginRequest;
use App\Interface\Api\Request\V1\SendVerificationCodeRequest;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;

final class H5SmsLoginTest extends TestCase
{
    public function testPhoneAndSixDigitCodeAreRequiredAndLoginIsAnAllowedScene(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key) => $key);
        $factory = new ValidatorFactory($translator);
        $request = (new \ReflectionClass(H5SmsLoginRequest::class))->newInstanceWithoutConstructor();
        self::assertTrue($factory->make(['phone' => '13800138000', 'code' => '001234'], $request->rules())->passes());
        foreach ([[], ['phone' => '123', 'code' => '001234'], ['phone' => '13800138000', 'code' => '12345'], ['phone' => '13800138000', 'code' => 123456], ['phone' => '13800138000', 'code' => 'abcdef']] as $input) {
            self::assertFalse($factory->make($input, $request->rules())->passes());
        }
        $send = (new \ReflectionClass(SendVerificationCodeRequest::class))->newInstanceWithoutConstructor();
        foreach (['register', 'forgot_password', 'login'] as $scene) {
            self::assertTrue($factory->make(['phone' => '13800138000', 'scene' => $scene], $send->rules())->passes());
        }
        self::assertFalse($factory->make(['phone' => '13800138000', 'scene' => 'admin'], $send->rules())->passes());
    }

    public function testSmsLoginUsesTheSameApiAccessAndRefreshPayload(): void
    {
        $input = new H5SmsLoginDto();
        $input->phone = '13800138000'; $input->code = '001234';
        $member = new MemberEntity();
        $member->setId(41); $member->setPhone($input->phone); $member->setNickname('会员'); $member->setSource('h5');
        $domain = $this->createMock(DomainApiMemberPasswordAuthService::class);
        $domain->expects(self::once())->method('loginBySms')->with($input)->willReturn($member);
        $tokens = $this->createMock(DomainTokenService::class);
        $tokens->expects(self::once())->method('using')->with('api')->willReturnSelf();
        $tokens->expects(self::once())->method('buildAccessToken')->with('member:41')->willReturn('access-token');
        $tokens->expects(self::once())->method('buildRefreshToken')->with('member:41')->willReturn('refresh-token');
        $tokens->method('getTtl')->willReturn(7200);
        $payload = (new AppApiAuthCommandService($domain, $tokens))->loginBySms($input);
        self::assertSame('access-token', $payload['token']);
        self::assertSame('refresh-token', $payload['refresh_token']);
        self::assertSame(7200, $payload['expires_in']);
        self::assertSame(41, $payload['member']['id']);
        self::assertSame($input->phone, $payload['member']['phone']);
        self::assertArrayNotHasKey('password', $payload['member']);
    }
}
