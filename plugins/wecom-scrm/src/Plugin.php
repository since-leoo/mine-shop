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

namespace Plugin\WecomScrm;

use SinceLeoo\Plugin\Contract\AbstractPlugin;

final class Plugin extends AbstractPlugin
{
    public function install(): void {}

    public function uninstall(): void {}

    public static function mallGroup(): array
    {
        return [
            'label' => '企业微信 SCRM',
            'description' => '企业微信连接、客户标签、群 SOP 与事件自动化。',
            'sort' => 75,
            'settings' => [
                'mall.wecom.enabled' => [
                    'label' => '启用企业微信 SCRM',
                    'type' => 'boolean',
                    'default' => true,
                    'sort' => 1,
                ],
                'mall.wecom.corp_id' => [
                    'label' => '企业 ID（CorpID）',
                    'type' => 'string',
                    'is_sensitive' => true,
                    'default' => '',
                    'sort' => 10,
                ],
                'mall.wecom.secret' => [
                    'label' => '应用 Secret',
                    'type' => 'string',
                    'is_sensitive' => true,
                    'default' => '',
                    'sort' => 20,
                ],
                'mall.wecom.token' => [
                    'label' => '回调 Token',
                    'type' => 'string',
                    'is_sensitive' => true,
                    'default' => '',
                    'sort' => 30,
                ],
                'mall.wecom.aes_key' => [
                    'label' => '回调 EncodingAESKey',
                    'type' => 'string',
                    'is_sensitive' => true,
                    'default' => '',
                    'sort' => 40,
                ],
                'mall.wecom.agent_id' => [
                    'label' => '自建应用 AgentId',
                    'description' => '用于企业微信应用消息发送。',
                    'type' => 'integer',
                    'default' => 0,
                    'sort' => 45,
                ],
                'mall.wecom.default_tags' => [
                    'label' => '事件默认标签映射',
                    'type' => 'json',
                    'default' => ['member_registered' => '新注册客户', 'order_paid' => '已成交客户'],
                    'sort' => 50,
                ],
            ]];
    }
}
