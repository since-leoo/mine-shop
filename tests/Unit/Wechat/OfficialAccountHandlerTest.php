<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Wechat;

use EasyWeChat\OfficialAccount\Application;
use Overtrue\Socialite\Contracts\UserInterface;
use Overtrue\Socialite\Providers\WeChat;
use Overtrue\Socialite\User;
use PHPUnit\Framework\TestCase;
use Plugin\Wechat\Construct\AbstractWechat;
use Plugin\Wechat\Handler\OfficialAccountHandler;

final class OfficialAccountHandlerTest extends TestCase
{
    public function testSdkUserKeepsTheExistingResponseShape(): void
    {
        $user = new User(['id' => 'openid', 'nickname' => 'fixture', 'raw' => ['openid' => 'openid']]);
        self::assertSame($user->toArray(), $this->handler($user)->getUserInfo('code'));
    }

    public function testUnsupportedProviderUserFailsWithoutCallingAnUndefinedMethod(): void
    {
        $handler = $this->handler($this->createMock(UserInterface::class));
        $this->expectException(\UnexpectedValueException::class);
        $handler->getUserInfo('code');
    }

    private function handler(UserInterface $user): OfficialAccountHandler
    {
        $oauth = $this->createMock(WeChat::class);
        $oauth->expects(self::once())->method('userFromCode')->with('code')->willReturn($user);
        $application = $this->createMock(Application::class);
        $application->method('getOAuth')->willReturn($oauth);
        $handler = (new \ReflectionClass(OfficialAccountHandler::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(AbstractWechat::class, 'app'))->setValue($handler, $application);
        return $handler;
    }
}
