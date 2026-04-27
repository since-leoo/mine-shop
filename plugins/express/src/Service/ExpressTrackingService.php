<?php

declare(strict_types=1);

namespace Plugin\Express\Service;

use Hyperf\Guzzle\ClientFactory;
use Plugin\Express\Contract\LogisticsTrackingInterface;
use Plugin\Express\Exception\TrackingException;
use Plugin\Express\Provider\Kuaidi100Provider;
use Plugin\Express\ValueObject\TrackingResult;

final class ExpressTrackingService implements LogisticsTrackingInterface
{
    public function __construct(
        private readonly ClientFactory $clientFactory,
        private readonly ExpressSettingsResolver $settingsResolver,
    ) {}

    public function track(string $companyCode, string $trackingNo): TrackingResult
    {
        $config = $this->settingsResolver->toArray();
        if (! ($config['enabled'] ?? false)) {
            throw new TrackingException('物流查询插件未启用');
        }

        return match ((string) ($config['default_provider'] ?? 'kuaidi100')) {
            'kuaidi100' => (new Kuaidi100Provider($this->clientFactory, $config))->track($companyCode, $trackingNo),
            default => throw new TrackingException('未支持的物流 provider'),
        };
    }
}
