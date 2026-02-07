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

namespace App\Domain\SystemSetting\ValueObject;

/**
 * 配送配置值对象.
 */
final class ShippingSetting
{
    /**
     * @param string[] $supportedProviders
     * @param string[] $remoteAreaProvinces
     * @param array<string, mixed> $defaultTemplateConfig
     */
    public function __construct(
        private readonly string $defaultMethod,
        private readonly bool $enablePickup,
        private readonly string $pickupAddress,
        private readonly int $freeShippingThreshold,
        private readonly array $supportedProviders,
        private readonly string $defaultFreightType = 'free',
        private readonly int $flatFreightAmount = 0,
        private readonly bool $remoteAreaEnabled = false,
        private readonly int $remoteAreaSurcharge = 0,
        private readonly array $remoteAreaProvinces = [],
        private readonly array $defaultTemplateConfig = [],
    ) {}

    public function defaultMethod(): string
    {
        return $this->defaultMethod;
    }

    public function enablePickup(): bool
    {
        return $this->enablePickup;
    }

    public function pickupAddress(): string
    {
        return $this->pickupAddress;
    }

    public function freeShippingThreshold(): int
    {
        return $this->freeShippingThreshold;
    }

    /**
     * @return string[]
     */
    public function supportedProviders(): array
    {
        return $this->supportedProviders;
    }

    public function isProviderSupported(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        return \in_array($code, $this->supportedProviders, true);
    }

    public function defaultFreightType(): string
    {
        return $this->defaultFreightType;
    }

    public function flatFreightAmount(): int
    {
        return $this->flatFreightAmount;
    }

    public function remoteAreaEnabled(): bool
    {
        return $this->remoteAreaEnabled;
    }

    public function remoteAreaSurcharge(): int
    {
        return $this->remoteAreaSurcharge;
    }

    /**
     * @return string[]
     */
    public function remoteAreaProvinces(): array
    {
        return $this->remoteAreaProvinces;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultTemplateConfig(): array
    {
        return $this->defaultTemplateConfig;
    }
}
