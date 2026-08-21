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

namespace Plugin\CustomerService\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;

final class CustomerServiceSettingsResolver
{
    private const KEY = 'mall.customer_service.config';

    public function __construct(private readonly DomainSystemSettingService $settings) {}

    public function toArray(): array
    {
        $defaults = [
            'enabled' => true, 'gateway_url' => '', 'socket_path' => '/customer-service',
            'queue_enabled' => true, 'auto_assign_strategy' => 'least_load',
            'allow_member_image' => true, 'allow_product_card' => true,
            'max_image_size_mb' => 5, 'max_message_length' => 1000,
            'welcome_message' => '您好，请问有什么可以帮助您？',
            'offline_message' => '当前暂无客服在线，请留下您的问题。',
        ];
        $value = $this->settings->get(self::KEY, $defaults);
        return \is_array($value) ? array_replace($defaults, $value) : $defaults;
    }
}
