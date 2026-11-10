<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Distribution;

use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Domain\Repository\DistributionRepository;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Distribution\Infrastructure\WechatShareGateway;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\Container\ContainerInterface;

final class WechatShareGatewayTest extends TestCase
{
    public function testUnlimitedCodeCarriesOnlyTheShortTokenEvenForLongProductParameters(): void
    {
        $token = str_repeat('a', 24);
        $file = str_repeat('b', 64) . '.png';
        $wechat = $this->createMock(MiniAppInterface::class);
        $wechat->expects(self::once())->method('getLimitedWxaCode')
            ->with('plugins/distribution/pages/landing/index', 's=' . $token, true)
            ->willReturn(['path' => '/uploadfile/wechat/mini/qrcode/' . $file]);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(MiniAppInterface::class)->willReturn(true);
        $container->method('get')->with(MiniAppInterface::class)->willReturn($wechat);
        $query = http_build_query([
            'spuId' => 123, 'skuId' => 456, 'activityId' => 789, 'orderType' => 'group_buy',
            'sessionId' => 18, 'groupBuyId' => 19, 'groupNo' => str_repeat('G', 64), 'share_token' => $token,
        ]);
        self::assertGreaterThan(128, strlen($query));
        self::assertSame(['image_url' => '/api/v1/distribution/share/images/' . $file],
            (new WechatShareGateway($container))->generate('qrcode', 'pages/goods/details/index', $query));
    }

    public function testMissingTokenCannotCreateAnUnresolvableCode(): void
    {
        $wechat = $this->createMock(MiniAppInterface::class);
        $wechat->expects(self::never())->method('getLimitedWxaCode');
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($wechat);
        $this->expectException(\DomainException::class);
        (new WechatShareGateway($container))->generate('qrcode', 'pages/goods/details/index', 'spuId=123');
    }

    public function testDisabledAttributionStillResolvesTheStoredProductForBrowsing(): void
    {
        $repository = $this->createMock(DistributionRepository::class);
        $repository->method('settings')->willReturn(['settings' => ['enabled' => false]]);
        $target = ['target' => 'product', 'path' => '/pages/goods/details/index', 'query' => ['spuId' => 123]];
        $repository->method('share')->willReturn(['member_id' => 1, 'target' => $target]);
        $repository->expects(self::never())->method('member');
        $repository->expects(self::never())->method('directCount');
        $result = (new DomainDistributionService($repository))->resolve(null, str_repeat('a', 24));
        self::assertSame(['valid' => false, 'reason' => 'disabled'] + $target, $result);
        self::assertArrayNotHasKey('referrer_id', $result);
        self::assertArrayNotHasKey('invite_code', $result);
    }

    public function testExpiredShareDoesNotReturnAProductDestination(): void
    {
        $repository = $this->createMock(DistributionRepository::class);
        $repository->method('settings')->willReturn(['settings' => ['enabled' => false]]);
        $repository->method('share')->willReturn(null);
        self::assertSame(['valid' => false, 'reason' => 'expired'],
            (new DomainDistributionService($repository))->resolve(null, str_repeat('a', 24)));
    }
}
