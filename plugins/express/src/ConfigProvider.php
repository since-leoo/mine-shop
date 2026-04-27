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
                    'express' => [
                        'label' => '物流查询',
                        'description' => '物流轨迹查询插件配置',
                        'sort' => 75,
                        'settings' => [
                            'mall.express.enabled' => [
                                'label' => '启用物流查询',
                                'description' => '关闭后不允许调用物流轨迹查询能力',
                                'type' => 'boolean',
                                'default' => true,
                                'sort' => 10,
                            ],
                            'mall.express.default_provider' => [
                                'label' => '默认服务商',
                                'description' => '当前物流轨迹查询默认使用的服务商',
                                'type' => 'string',
                                'meta' => [
                                    'component' => 'select',
                                    'options' => [
                                        ['label' => '快递100', 'value' => 'kuaidi100'],
                                    ],
                                ],
                                'default' => 'kuaidi100',
                                'sort' => 20,
                            ],
                            'mall.express.customer' => [
                                'label' => 'Customer',
                                'description' => '快递100 customer 参数',
                                'type' => 'string',
                                'default' => '',
                                'sort' => 30,
                            ],
                            'mall.express.key' => [
                                'label' => 'Key',
                                'description' => '快递100 key 参数',
                                'type' => 'string',
                                'is_sensitive' => true,
                                'meta' => [
                                    'input_type' => 'password',
                                ],
                                'default' => '',
                                'sort' => 40,
                            ],
                            'mall.express.endpoint' => [
                                'label' => '查询地址',
                                'description' => '物流查询接口地址',
                                'type' => 'string',
                                'default' => 'https://poll.kuaidi100.com/poll/query.do',
                                'sort' => 50,
                            ],
                            'mall.express.cache_ttl' => [
                                'label' => '缓存时长',
                                'description' => '物流查询缓存时长，单位秒',
                                'type' => 'integer',
                                'default' => 300,
                                'sort' => 60,
                            ],
                            'mall.express.timeout' => [
                                'label' => '请求超时',
                                'description' => '物流查询请求超时时间，单位秒',
                                'type' => 'integer',
                                'default' => 5,
                                'sort' => 70,
                            ],
                            'mall.express.company_name_map' => [
                                'label' => '公司名称映射',
                                'description' => '配置物流公司编码与展示名称映射',
                                'type' => 'json',
                                'meta' => [
                                    'component' => 'collection',
                                    'display' => 'dialog',
                                    'button_label' => '配置映射',
                                    'add_label' => '新增映射',
                                    'fields' => [
                                        [
                                            'key' => 'code',
                                            'label' => '公司编码',
                                            'required' => true,
                                        ],
                                        [
                                            'key' => 'name',
                                            'label' => '展示名称',
                                            'required' => true,
                                        ],
                                    ],
                                ],
                                'default' => [],
                                'sort' => 80,
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
