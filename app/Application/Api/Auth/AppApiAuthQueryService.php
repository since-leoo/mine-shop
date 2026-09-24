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

namespace App\Application\Api\Auth;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;

final class AppApiAuthQueryService
{
    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

    public function registerProtocols(): array
    {
        $basic = $this->mallSettingService->basic();
        $content = $this->mallSettingService->content();

        return [
            'userAgreement' => $basic->userAgreement(),
            'privacyPolicy' => $basic->privacyPolicy(),
            'termsUrl' => $content->termsUrl(),
            'privacyPolicyUrl' => $content->privacyPolicyUrl(),
            'supportEmail' => $basic->supportEmail(),
            'hotline' => $basic->hotline(),
            'miniappLogo' => $basic->miniappLogo(),
            'complianceEmail' => $content->complianceEmail(),
        ];
    }
}
