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

namespace App\Domain\SystemSetting\Service;

use App\Domain\SystemSetting\ValueObject\BasicSetting;
use App\Domain\SystemSetting\ValueObject\ContentSetting;
use App\Domain\SystemSetting\ValueObject\IntegrationSetting;
use App\Domain\SystemSetting\ValueObject\MemberSetting;
use App\Domain\SystemSetting\ValueObject\OrderSetting;
use App\Domain\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\SystemSetting\ValueObject\ProductSetting;
use App\Domain\SystemSetting\ValueObject\ShippingSetting;
use App\Infrastructure\Abstract\IService;

/**
 * 商城配置聚合服务.
 *
 * 以值对象的形式对外暴露商城相关配置，避免业务层直接依赖数组。
 */
final class DomainMallSettingService extends IService
{
    private ?BasicSetting $basic = null;

    private ?ProductSetting $product = null;

    private ?OrderSetting $order = null;

    private ?PaymentSetting $payment = null;

    private ?ShippingSetting $shipping = null;

    private ?MemberSetting $member = null;

    private ?ContentSetting $content = null;

    private ?IntegrationSetting $integration = null;

    public function __construct(private readonly DomainSystemSettingService $settingService) {}

    public function basic(): BasicSetting
    {
        return $this->basic ??= new BasicSetting(
            (string) $this->value('mall.basic.name', 'MineMall 商城'),
            (string) $this->value('mall.basic.logo', ''),
            (string) $this->value('mall.basic.support_email', 'support@minemall.local'),
            (string) $this->value('mall.basic.hotline', '400-888-0000'),
        );
    }

    public function product(): ProductSetting
    {
        return $this->product ??= new ProductSetting(
            (bool) $this->value('mall.product.auto_generate_sku', true),
            (int) $this->value('mall.product.max_gallery', 9),
            (int) $this->value('mall.product.stock_warning', 20),
            (bool) $this->value('mall.product.allow_preorder', false),
            $this->normalizeLines($this->value('mall.product.content_filter', [])),
        );
    }

    public function order(): OrderSetting
    {
        return $this->order ??= new OrderSetting(
            (int) $this->value('mall.order.auto_close_minutes', 30),
            (int) $this->value('mall.order.auto_confirm_days', 7),
            (int) $this->value('mall.order.after_sale_days', 15),
            (bool) $this->value('mall.order.enable_invoice', true),
            (string) $this->value('mall.order.invoice_provider', 'system'),
            (string) $this->value('mall.order.customer_service', '400-888-1000'),
        );
    }

    public function payment(): PaymentSetting
    {
        return $this->payment ??= new PaymentSetting(
            (bool) $this->value('mall.payment.wechat_enabled', false),
            $this->normalizeArray($this->value('mall.payment.wechat_config', [])),
            (bool) $this->value('mall.payment.refund_review', true),
            (int) $this->value('mall.payment.settlement_cycle_days', 7),
            (bool) $this->value('mall.payment.balance_enabled', true),
            $this->normalizeArray($this->value('mall.payment.balance_config', [])),
        );
    }

    public function shipping(): ShippingSetting
    {
        $flatFreightConfig = $this->normalizeArray($this->value('mall.shipping.flat_freight_amount', []));
        $remoteAreaConfig = $this->normalizeArray($this->value('mall.shipping.remote_area_surcharge', []));

        return $this->shipping ??= new ShippingSetting(
            (string) $this->value('mall.shipping.default_method', 'express'),
            (bool) $this->value('mall.shipping.enable_pickup', true),
            (string) $this->value('mall.shipping.pickup_address', ''),
            (int) $this->value('mall.shipping.free_shipping_threshold', 0),
            $this->normalizeStringArray($this->value('mall.shipping.supported_providers', [])),
            (string) $this->value('mall.shipping.default_freight_type', 'free'),
            (int) ($flatFreightConfig['amount'] ?? 0),
            (bool) $this->value('mall.shipping.remote_area_enabled', false),
            (int) ($remoteAreaConfig['surcharge'] ?? 0),
            $this->normalizeStringArray($remoteAreaConfig['provinces'] ?? []),
            $this->normalizeArray($this->value('mall.shipping.default_template_config', [])),
        );
    }

    public function member(): MemberSetting
    {
        return $this->member ??= new MemberSetting(
            (bool) $this->value('mall.member.enable_growth', true),
            (int) $this->value('mall.member.register_points', 100),
            (int) $this->value('mall.member.sign_in_reward', 5),
            (int) $this->value('mall.member.invite_reward', 50),
            (int) $this->value('mall.member.points_expire_months', 24),
            $this->normalizeLevelDefinitions($this->value('mall.member.vip_levels', [])),
            (int) $this->value('mall.member.default_level', 1),
            (int) $this->value('mall.member.points_ratio', 100),
        );
    }

    public function content(): ContentSetting
    {
        return $this->content ??= new ContentSetting(
            $this->normalizeLines($this->value('mall.content.prohibited_keywords', [])),
            (string) $this->value('mall.content.privacy_policy_url', '/pages/policies/privacy'),
            (string) $this->value('mall.content.terms_url', '/pages/policies/terms'),
            (string) $this->value('mall.content.compliance_email', 'compliance@minemall.local'),
        );
    }

    public function integration(): IntegrationSetting
    {
        return $this->integration ??= new IntegrationSetting(
            (string) $this->value('mall.integration.sms_provider', 'aliyun'),
            $this->normalizeArray($this->value('mall.integration.sms_config', [])),
            $this->normalizeChannels($this->value('mall.integration.notif_channels', [
                'mail' => true,
                'sms' => false,
                'system' => true,
            ])),
            (string) $this->value('mall.integration.sms_template', ''),
            (string) $this->value('mall.integration.email_template', ''),
            (string) $this->value('mall.integration.webhook_url', ''),
            (bool) $this->value('mall.integration.audit_mode', false),
        );
    }

    private function value(string $key, mixed $default = null): mixed
    {
        return $this->settingService->get($key, $default);
    }

    /**
     * @return string[]
     */
    private function normalizeLines(mixed $value): array
    {
        $lines = [];
        if (\is_array($value)) {
            $lines = $value;
        } elseif (\is_string($value)) {
            $lines = preg_split("/\r\n|\r|\n/", $value) ?: [];
        }

        return array_values(array_filter(array_map(static function ($line) {
            return trim((string) $line);
        }, $lines), static fn ($line) => $line !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        return \is_array($value) ? $value : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLevelDefinitions(mixed $value): array
    {
        if (! \is_array($value)) {
            return [];
        }

        $levels = [];
        foreach ($value as $item) {
            if (\is_array($item)) {
                $levels[] = $item;
            }
        }

        return $levels;
    }

    /**
     * @return string[]
     */
    private function normalizeStringArray(mixed $value): array
    {
        if (! \is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item) {
            return trim((string) $item);
        }, $value), static fn ($item) => $item !== ''));
    }

    /**
     * @return array<string, bool>
     */
    private function normalizeChannels(mixed $value): array
    {
        $channels = $this->normalizeArray($value);
        foreach ($channels as $channel => $enabled) {
            $channels[$channel] = (bool) $enabled;
        }
        return $channels;
    }
}
