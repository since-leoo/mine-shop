<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Service\DomainConfigurationService;

final class CustomerServiceSettingsResolver
{
    public function __construct(private readonly DomainSystemSettingService $settings, private readonly SupportConfigurationInterface $configuration) {}

    public function toArray(): array
    {
        $legacy = $this->settings->get('mall.customer_service.config', []);
        $legacy = is_array($legacy) ? $legacy : [];
        if (isset($legacy['auto_assign_strategy']) && !isset($legacy['assignment_strategy'])) { $legacy['assignment_strategy'] = $legacy['auto_assign_strategy']; }
        $legacy = array_intersect_key($legacy, array_flip([...array_keys(DomainConfigurationService::DEFAULTS), 'gateway_url', 'socket_path']));
        $published = $this->configuration->published();
        $settings = array_replace(DomainConfigurationService::DEFAULTS, $legacy, $published['snapshot']['settings'] ?? []);
        return $settings + ['gateway_url' => '', 'socket_path' => '/customer-service'];
    }
}
