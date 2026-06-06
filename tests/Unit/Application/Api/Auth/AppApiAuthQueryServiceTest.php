<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Application\Api\Auth;

use App\Application\Api\Auth\AppApiAuthQueryService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\BasicSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ContentSetting;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AppApiAuthQueryServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testRegisterProtocolsExposeAgreementAndSupportSettings(): void
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('basic')->willReturn(new BasicSetting(
            mallName: 'MineMall',
            adminLogo: '/admin.svg',
            adminSmallLogo: '/admin-small.svg',
            loginLogo: '/login.svg',
            miniappLogo: '/miniapp.svg',
            favicon: '/favicon.ico',
            logo: '/logo.svg',
            userAgreement: 'User agreement text',
            privacyPolicy: 'Privacy policy text',
            supportEmail: 'support@example.com',
            hotline: '400-888-0000',
        ));
        $settings->method('content')->willReturn(new ContentSetting(
            prohibitedKeywords: [],
            privacyPolicyUrl: '/pages/privacy',
            termsUrl: '/pages/terms',
            complianceEmail: 'compliance@example.com',
        ));

        $data = (new AppApiAuthQueryService($settings))->registerProtocols();

        self::assertSame('User agreement text', $data['userAgreement']);
        self::assertSame('Privacy policy text', $data['privacyPolicy']);
        self::assertSame('/pages/terms', $data['termsUrl']);
        self::assertSame('/pages/privacy', $data['privacyPolicyUrl']);
        self::assertSame('support@example.com', $data['supportEmail']);
        self::assertSame('400-888-0000', $data['hotline']);
        self::assertSame('/miniapp.svg', $data['miniappLogo']);
        self::assertSame('compliance@example.com', $data['complianceEmail']);
    }
}
