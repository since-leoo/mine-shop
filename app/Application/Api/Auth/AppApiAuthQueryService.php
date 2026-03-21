<?php

declare(strict_types=1);

namespace App\Application\Api\Auth;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;

final class AppApiAuthQueryService
{
    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

    /**
     * @return array{userAgreement: string, privacyPolicy: string}
     */
    public function registerProtocols(): array
    {
        $basic = $this->mallSettingService->basic();

        return [
            'userAgreement' => $basic->userAgreement(),
            'privacyPolicy' => $basic->privacyPolicy(),
        ];
    }
}
