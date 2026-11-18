<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\Express\Service;

use App\Infrastructure\Error\ExpressError;
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
        if (! $config['enabled']) {
            throw new TrackingException(ExpressError::PLUGIN_DISABLED->message(), ExpressError::PLUGIN_DISABLED->value);
        }

        return match ($config['default_provider']) {
            'kuaidi100' => (new Kuaidi100Provider($this->clientFactory, $config))->track($companyCode, $trackingNo),
            default => throw new TrackingException(ExpressError::PROVIDER_UNSUPPORTED->message(), ExpressError::PROVIDER_UNSUPPORTED->value),
        };
    }
}
