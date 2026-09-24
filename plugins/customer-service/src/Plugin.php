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

namespace Plugin\CustomerService;

use SinceLeoo\Plugin\Contract\AbstractPlugin;

final class Plugin extends AbstractPlugin
{
    public static function mallGroups(): array
    {
        return [
            'customer_service' => [
                'label' => '客服中心',
                'description' => '配置 Socket 客服服务、排队、消息和营业时间。',
                'sort' => 70,
                'settings' => [
                    'mall.customer_service.config' => [
                        'label' => '客服服务配置',
                        'description' => '客服 Socket 服务运行参数及业务策略。',
                        'type' => 'json',
                        'is_sensitive' => true,
                        'meta' => [
                            'component' => 'form',
                            'display' => 'dialog',
                            'button_label' => '配置客服',
                            'fields' => [
                                ['key' => 'enabled', 'label' => '启用客服', 'component' => 'switch'],
                                ['key' => 'gateway_url', 'label' => '客服 Socket 地址', 'placeholder' => 'wss://socket.example.com:9502'],
                                ['key' => 'socket_path', 'label' => 'Socket 路径', 'required' => true, 'placeholder' => '/customer-service'],
                                ['key' => 'max_queue_size', 'label' => '最大排队人数', 'component' => 'number', 'required' => true],
                                ['key' => 'queue_enabled', 'label' => '启用排队', 'component' => 'switch'],
                                ['key' => 'auto_assign_strategy', 'label' => '自动分配策略', 'component' => 'select', 'options' => [['label' => '最少接待优先', 'value' => 'least_load']]],
                                ['key' => 'queue_timeout_seconds', 'label' => '排队超时秒数', 'component' => 'number', 'required' => true],
                                ['key' => 'max_conversations_per_agent', 'label' => '坐席最大会话数', 'component' => 'number', 'required' => true],
                                ['key' => 'history_page_size', 'label' => '历史消息条数', 'component' => 'number', 'required' => true],
                                ['key' => 'allow_member_image', 'label' => '允许发送图片', 'component' => 'switch'],
                                ['key' => 'allow_product_card', 'label' => '允许发送商品卡片', 'component' => 'switch'],
                                ['key' => 'max_image_size_mb', 'label' => '图片大小 MB', 'component' => 'number', 'required' => true],
                                ['key' => 'max_message_length', 'label' => '文字最大长度', 'component' => 'number', 'required' => true],
                                ['key' => 'welcome_message', 'label' => '欢迎语', 'component' => 'textarea'],
                                ['key' => 'offline_message', 'label' => '离线提示', 'component' => 'textarea'],
                            ],
                        ],
                        'default' => [
                            'enabled' => true,
                            'gateway_url' => '',
                            'socket_path' => '/customer-service',
                            'max_queue_size' => 100,
                            'queue_enabled' => true,
                            'auto_assign_strategy' => 'least_load',
                            'queue_timeout_seconds' => 600,
                            'max_conversations_per_agent' => 5,
                            'history_page_size' => 30,
                            'allow_member_image' => true,
                            'allow_product_card' => true,
                            'max_image_size_mb' => 5,
                            'max_message_length' => 1000,
                            'welcome_message' => '您好，请问有什么可以帮助您？',
                            'offline_message' => '当前暂无客服在线，请留下您的问题。',
                        ],
                        'sort' => 10,
                    ],
                ],
            ],
        ];
    }

    public function install(): void
    {
        $webSource = \dirname(__DIR__) . '/web';
        $webTarget = BASE_PATH . '/web/src/plugins/since/customer-service';
        if (is_dir($webSource)) {
            $this->copyDirectory($webSource, $webTarget);
        }
        // 插件统一通过前端插件中心提供入口，不再向主应用写入独立菜单。
        // 权限按钮仍由业务接口按权限标识校验，历史菜单由 uninstallMenus() 清理。
    }

    public function uninstall(): void
    {
        $webTarget = BASE_PATH . '/web/src/plugins/since/customer-service';
        if (is_dir($webTarget)) {
            $this->deleteDirectory($webTarget);
        }
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (! is_dir($target)) {
            mkdir($target, 0o755, true);
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $targetPath = $target . \DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0o755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    private function deleteDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
