<?php

declare(strict_types=1);

namespace Plugin\Express;

use Plugin\Express\Contract\LogisticsTrackingInterface;
use Plugin\Express\Service\ExpressTrackingService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [],
            'dependencies' => [
                LogisticsTrackingInterface::class => ExpressTrackingService::class,
            ],
            'mall' => [
                'groups' => [
                    'shipping' => [
                        'settings' => [
                            'mall.shipping.express_tracking_config' => [
                                'label' => '物流查询配置',
                                'description' => '配置第三方物流轨迹查询服务参数。',
                                'type' => 'json',
                                'is_sensitive' => true,
                                'meta' => [
                                    'component' => 'form',
                                    'display' => 'dialog',
                                    'button_label' => '配置物流查询',
                                    'fields' => [
                                        [
                                            'key' => 'enabled',
                                            'label' => '启用物流查询',
                                            'component' => 'switch',
                                        ],
                                        [
                                            'key' => 'default_provider',
                                            'label' => '默认服务商',
                                            'component' => 'select',
                                            'required' => true,
                                            'options' => [
                                                ['label' => '快递100', 'value' => 'kuaidi100'],
                                            ],
                                        ],
                                        [
                                            'key' => 'customer',
                                            'label' => 'Customer',
                                            'required' => true,
                                            'placeholder' => '请输入快递100 customer',
                                        ],
                                        [
                                            'key' => 'key',
                                            'label' => 'Key',
                                            'required' => true,
                                            'input_type' => 'password',
                                            'placeholder' => '请输入快递100 key',
                                        ],
                                        [
                                            'key' => 'endpoint',
                                            'label' => '查询地址',
                                            'required' => true,
                                            'placeholder' => 'https://poll.kuaidi100.com/poll/query.do',
                                        ],
                                    ],
                                ],
                                'default' => [
                                    'enabled' => true,
                                    'default_provider' => 'kuaidi100',
                                    'customer' => '',
                                    'key' => '',
                                    'endpoint' => 'https://poll.kuaidi100.com/poll/query.do',
                                    'cache_ttl' => 300,
                                    'timeout' => 5,
                                ],
                                'sort' => 62,
                            ],
                        ],
                    ],
                ],
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
