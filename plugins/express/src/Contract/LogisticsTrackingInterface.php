<?php

declare(strict_types=1);

namespace Plugin\Express\Contract;

use Plugin\Express\ValueObject\TrackingResult;

interface LogisticsTrackingInterface
{
    public function track(string $companyCode, string $trackingNo): TrackingResult;
}
