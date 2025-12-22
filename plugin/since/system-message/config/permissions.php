<?php

declare(strict_types=1);

/**
 * 系统消息插件权限配置
 */
return [
    // 系统消息管理权限
    'system-message:index' => [
        'name' => '系统消息列表',
        'description' => '查看系统消息列表',
        'group' => '系统消息管理',
    ],
    'system-message:read' => [
        'name' => '查看系统消息',
        'description' => '查看系统消息详情',
        'group' => '系统消息管理',
    ],
    'system-message:save' => [
        'name' => '创建系统消息',
        'description' => '创建新的系统消息',
        'group' => '系统消息管理',
    ],
    'system-message:update' => [
        'name' => '更新系统消息',
        'description' => '更新系统消息信息',
        'group' => '系统消息管理',
    ],
    'system-message:delete' => [
        'name' => '删除系统消息',
        'description' => '删除系统消息',
        'group' => '系统消息管理',
    ],
    'system-message:send' => [
        'name' => '发送系统消息',
        'description' => '发送系统消息给用户',
        'group' => '系统消息管理',
    ],
    'system-message:schedule' => [
        'name' => '调度系统消息',
        'description' => '设置系统消息定时发送',
        'group' => '系统消息管理',
    ],
    'system-message:batchSend' => [
        'name' => '批量发送消息',
        'description' => '批量发送系统消息',
        'group' => '系统消息管理',
    ],
    'system-message:statistics' => [
        'name' => '消息统计',
        'description' => '查看消息发送统计',
        'group' => '系统消息管理',
    ],

    // 消息模板管理权限
    'system-message-template:index' => [
        'name' => '消息模板列表',
        'description' => '查看消息模板列表',
        'group' => '消息模板管理',
    ],
    'system-message-template:read' => [
        'name' => '查看消息模板',
        'description' => '查看消息模板详情',
        'group' => '消息模板管理',
    ],
    'system-message-template:save' => [
        'name' => '创建消息模板',
        'description' => '创建新的消息模板',
        'group' => '消息模板管理',
    ],
    'system-message-template:update' => [
        'name' => '更新消息模板',
        'description' => '更新消息模板信息',
        'group' => '消息模板管理',
    ],
    'system-message-template:delete' => [
        'name' => '删除消息模板',
        'description' => '删除消息模板',
        'group' => '消息模板管理',
    ],
    'system-message-template:statistics' => [
        'name' => '模板统计',
        'description' => '查看模板使用统计',
        'group' => '消息模板管理',
    ],
    'system-message-template:import' => [
        'name' => '导入消息模板',
        'description' => '导入消息模板',
        'group' => '消息模板管理',
    ],
    'system-message-template:export' => [
        'name' => '导出消息模板',
        'description' => '导出消息模板',
        'group' => '消息模板管理',
    ],

    // 系统消息高级权限
    'system-message:admin' => [
        'name' => '系统消息管理员',
        'description' => '系统消息模块完全管理权限',
        'group' => '系统消息管理',
    ],
];