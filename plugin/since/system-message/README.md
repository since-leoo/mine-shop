# System Message 系统消息插件

一个功能完善的 MineAdmin 系统消息通知插件，支持多渠道消息推送、消息模板、用户偏好设置等功能。

## ✨ 功能特性

- 📨 **多渠道消息推送** - 支持站内信、邮件、短信、APP推送等多种通知渠道
- 📝 **消息模板管理** - 支持自定义消息模板，变量替换，模板预览
- 👥 **灵活的收件人** - 支持全员、指定用户、指定角色、指定部门等多种发送方式
- ⚙️ **用户偏好设置** - 用户可自定义接收渠道、消息类型、免打扰时间
- 📊 **消息统计分析** - 提供消息发送统计、阅读率分析等数据
- 🔄 **异步队列发送** - 支持队列异步发送，可配置延迟发送
- 📅 **定时消息** - 支持消息定时发送功能
- 🎯 **消息优先级** - 支持1-5级消息优先级设置

## � 目录结构

```
plugin/since/system-message/
├── config/                          # 配置文件目录
│   └── system_message.php           # 插件配置文件
├── Database/                        # 数据库相关
│   ├── Migrations/                  # 数据库迁移文件
│   │   ├── 2024_12_21_000001_create_system_messages_table.php
│   │   ├── 2024_12_21_000002_create_user_messages_table.php
│   │   ├── 2024_12_21_000003_create_message_templates_table.php
│   │   ├── 2024_12_21_000004_create_user_notification_preferences_table.php
│   │   └── 2024_12_21_000005_create_message_delivery_logs_table.php
│   └── Seeders/                     # 数据填充文件
├── src/                             # PHP 源代码目录
│   ├── Contract/                    # 接口契约
│   │   ├── AbstractMessageTemplate.php
│   │   └── MessageTemplateInterface.php
│   ├── Controller/                  # 控制器
│   │   ├── Admin/                   # 管理端控制器
│   │   │   ├── MessageController.php
│   │   │   └── TemplateController.php
│   │   ├── User/                    # 用户端控制器
│   │   │   ├── PreferenceController.php
│   │   │   └── UserMessageController.php
│   │   └── AbstractController.php
│   ├── Enum/                        # 枚举类
│   │   ├── MessageChannel.php       # 消息渠道枚举
│   │   ├── MessageStatus.php        # 消息状态枚举
│   │   ├── MessageType.php          # 消息类型枚举
│   │   └── RecipientType.php        # 收件人类型枚举
│   ├── Event/                       # 事件类
│   │   ├── MessageSendFailed.php    # 消息发送失败事件
│   │   ├── MessageSent.php          # 消息发送成功事件
│   │   ├── SendMessageEvent.php     # 发送消息事件
│   │   └── TemplateMessageEvent.php # 模板消息事件
│   ├── Facade/                      # 门面类
│   │   └── SystemMessage.php        # 系统消息门面
│   ├── Helper/                      # 辅助函数
│   │   └── helper.php               # 全局辅助函数
│   ├── Job/                         # 队列任务
│   │   ├── ProcessMessageEventJob.php
│   │   └── SendMessageJob.php
│   ├── Listener/                    # 事件监听器
│   │   └── SendMessageListener.php
│   ├── Model/                       # 数据模型
│   │   ├── Message.php              # 消息模型
│   │   ├── MessageDeliveryLog.php   # 发送日志模型
│   │   ├── MessageTemplate.php      # 消息模板模型
│   │   ├── UserMessage.php          # 用户消息模型
│   │   └── UserNotificationPreference.php
│   ├── Repository/                  # 数据仓库
│   │   ├── MessageRepository.php
│   │   ├── TemplateRepository.php
│   │   └── UserPreferenceRepository.php
│   ├── Request/                     # 请求验证
│   │   ├── CreateMessageRequest.php
│   │   ├── CreateTemplateRequest.php
│   │   ├── UpdateMessageRequest.php
│   │   ├── UpdatePreferenceRequest.php
│   │   └── UpdateTemplateRequest.php
│   ├── Service/                     # 业务服务
│   │   ├── MessageService.php       # 消息服务
│   │   ├── NotificationService.php  # 通知服务
│   │   └── TemplateService.php      # 模板服务
│   ├── Template/                    # 内置模板
│   │   ├── AlertNotification.php
│   │   ├── AnnouncementNotification.php
│   │   ├── ReminderNotification.php
│   │   └── SystemNotification.php
│   ├── ConfigProvider.php           # 配置提供者
│   ├── InstallScript.php            # 安装脚本
│   └── UninstallScript.php          # 卸载脚本
├── web/                             # 前端源代码目录
│   ├── api/                         # API 接口封装
│   │   ├── index.ts
│   │   ├── message.ts
│   │   ├── preference.ts
│   │   └── template.ts
│   ├── components/                  # Vue 组件
│   │   └── MessageNotificationBadge.vue
│   ├── locales/                     # 国际化语言包
│   │   ├── en_US.ts
│   │   ├── index.ts
│   │   └── zh_CN.ts
│   ├── overrides/                   # 覆盖文件
│   │   ├── notification.original.tsx  # 原始通知组件备份
│   │   └── notification.tsx           # 覆盖的通知组件
│   ├── store/                       # Pinia 状态管理
│   │   ├── index.ts
│   │   ├── message.ts
│   │   ├── preference.ts
│   │   └── template.ts
│   ├── utils/                       # 工具函数
│   │   └── message.ts
│   ├── views/                       # 页面视图
│   │   ├── admin/                   # 管理端页面
│   │   │   ├── AdminDashboard.vue   # 消息统计仪表盘
│   │   │   ├── AdminMessageForm.vue # 消息表单
│   │   │   ├── AdminMessageList.vue # 消息列表
│   │   │   ├── AdminTemplateForm.vue
│   │   │   └── AdminTemplateList.vue
│   │   ├── MessageCenter.vue        # 消息中心
│   │   ├── MessageDetail.vue        # 消息详情
│   │   ├── MessageList.vue          # 用户消息列表
│   │   └── NotificationSettings.vue # 通知设置
│   ├── index.ts                     # 前端入口
│   └── package.json
├── mine.json                        # 插件元信息
└── README.md                        # 说明文档
```

## 📦 安装

### 通过应用商店安装

在 MineAdmin 应用商店中搜索 "System Message" 并安装。

### 手动安装

1. 下载插件:
```bash
php bin/hyperf.php mine-extension:download since/system-message --yes
```
2. 安装插件：
```bash
php bin/hyperf.php mine-extension:install since/system-message --yes
```


## 🚀 快速开始

### 发送简单消息

```php
use Plugin\Since\SystemMessage\Facade\SystemMessage;

// 发送给所有用户
SystemMessage::sendToAll('系统通知', '这是一条系统通知消息');

// 发送给指定用户
SystemMessage::sendToUser(1, '个人消息', '这是发送给您的消息');

// 发送给多个用户
SystemMessage::sendToUsers([1, 2, 3], '群发消息', '这是群发消息内容');
```

### 使用模板发送

```php
use Plugin\Since\SystemMessage\Facade\SystemMessage;

// 使用模板发送消息
SystemMessage::sendTemplate(
    templateId: 1,
    userIds: [1, 2, 3],
    variables: [
        'username' => '张三',
        'order_no' => 'ORD202412240001',
        'amount' => '99.00'
    ]
);
```

### 使用事件发送（推荐）

```php
use Plugin\Since\SystemMessage\Event\SendMessageEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

// 注入事件分发器
public function __construct(
    private EventDispatcherInterface $eventDispatcher
) {}

// 发送消息
$this->eventDispatcher->dispatch(new SendMessageEvent(
    title: '订单通知',
    content: '您的订单已发货',
    userIds: [1],
    type: 'system',
    channels: ['database', 'email'],
    useQueue: true,  // 使用队列异步发送
    queueDelay: 0    // 延迟秒数
));
```

### 使用模板事件发送

```php
use Plugin\Since\SystemMessage\Event\TemplateMessageEvent;

$this->eventDispatcher->dispatch(new TemplateMessageEvent(
    templateId: 1,
    userIds: [1, 2, 3],
    variables: [
        'username' => '张三',
        'code' => '123456'
    ],
    useQueue: true
));
```

## 📖 开发者指南

### 消息类型

| 类型 | 值 | 说明 |
|------|-----|------|
| 系统通知 | `system` | 系统级别的通知消息 |
| 公告 | `announcement` | 全员公告消息 |
| 警告 | `alert` | 警告提醒消息 |
| 提醒 | `reminder` | 普通提醒消息 |
| 营销 | `marketing` | 营销推广消息 |

### 消息渠道

| 渠道 | 值 | 说明 |
|------|-----|------|
| 站内信 | `database` | 存储到数据库，前端轮询获取 |
| 邮件 | `email` | 发送邮件通知 |
| 短信 | `sms` | 发送短信通知 |
| APP推送 | `push` | APP推送通知 |

### 收件人类型

| 类型 | 值 | 说明 |
|------|-----|------|
| 全部用户 | `all` | 发送给所有用户 |
| 指定用户 | `users` | 发送给指定的用户列表 |
| 指定角色 | `roles` | 发送给指定角色的用户 |
| 指定部门 | `departments` | 发送给指定部门的用户 |

### 服务类使用

```php
use Plugin\Since\SystemMessage\Service\MessageService;
use Plugin\Since\SystemMessage\Service\TemplateService;
use Plugin\Since\SystemMessage\Service\NotificationService;

class YourService
{
    public function __construct(
        private MessageService $messageService,
        private TemplateService $templateService,
        private NotificationService $notificationService
    ) {}

    public function sendNotification()
    {
        // 创建消息
        $message = $this->messageService->create([
            'title' => '消息标题',
            'content' => '消息内容',
            'type' => 'system',
            'recipient_type' => 'users',
            'recipient_ids' => [1, 2, 3],
            'channels' => ['database', 'email'],
            'priority' => 3,
        ]);

        // 发送消息
        $this->messageService->send($message->id);
    }
}
```

### 获取用户消息

```php
// 获取用户消息列表
$messages = $this->messageService->getUserMessages(
    userId: 1,
    filters: [
        'is_read' => false,  // 只获取未读
        'type' => 'system',  // 只获取系统消息
    ],
    page: 1,
    pageSize: 20
);

// 获取未读消息数量
$unreadCount = $this->messageService->getUnreadCount(userId: 1);

// 标记消息为已读
$this->messageService->markAsRead(userId: 1, messageId: 100);

// 标记所有消息为已读
$this->messageService->markAllAsRead(userId: 1);
```

### 消息模板

```php
// 创建模板
$template = $this->templateService->create([
    'name' => 'order_shipped',
    'title_template' => '订单发货通知',
    'content_template' => '尊敬的{{username}}，您的订单{{order_no}}已发货，请注意查收。',
    'type' => 'system',
    'variables' => ['username', 'order_no'],
]);

// 渲染模板
$rendered = $this->templateService->render(
    templateId: $template->id,
    variables: [
        'username' => '张三',
        'order_no' => 'ORD001'
    ]
);
// 结果: ['title' => '订单发货通知', 'content' => '尊敬的张三，您的订单ORD001已发货，请注意查收。']
```

### 用户偏好设置

```php
// 获取用户偏好
$preference = $this->notificationService->getUserPreference(userId: 1);

// 更新用户偏好
$this->notificationService->updateUserPreference(userId: 1, data: [
    'channel_preferences' => [
        'database' => true,
        'email' => true,
        'sms' => false,
        'push' => false,
    ],
    'type_preferences' => [
        'system' => true,
        'announcement' => true,
        'marketing' => false,
    ],
    'do_not_disturb_enabled' => true,
    'do_not_disturb_start' => '22:00:00',
    'do_not_disturb_end' => '08:00:00',
    'min_priority' => 2,  // 只接收优先级>=2的消息
]);
```


## 🔧 配置说明

插件安装后会在 `config/autoload/system_message.php` 生成配置文件：

```php
return [
    // 消息配置
    'message' => [
        'max_title_length' => 255,      // 标题最大长度
        'max_content_length' => 10000,  // 内容最大长度
        'default_priority' => 1,        // 默认优先级
        'retention_days' => 90,         // 消息保留天数
    ],
    
    // 通知配置
    'notification' => [
        'retry' => [
            'max_attempts' => 3,        // 发送失败重试次数
        ],
        'default_channels' => [         // 默认启用的渠道
            'database' => true,
            'email' => false,
            'sms' => false,
            'push' => false,
        ],
        'default_types' => [            // 默认启用的消息类型
            'system' => true,
            'announcement' => true,
            'alert' => true,
            'reminder' => true,
            'marketing' => false,
        ],
    ],
    
    // 模板配置
    'template' => [
        'variable_pattern' => '/\{\{(\w+)\}\}/',  // 变量匹配模式
        'max_name_length' => 100,                  // 模板名称最大长度
    ],
    
    // 队列配置
    'queue' => [
        'channel' => 'default',         // 队列通道名称
    ],
];
```

## 📡 API 接口

### 管理端接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/plugin/admin/system-message/index` | 获取消息列表 |
| GET | `/plugin/admin/system-message/read/{id}` | 获取消息详情 |
| POST | `/plugin/admin/system-message/save` | 创建消息 |
| PUT | `/plugin/admin/system-message/update/{id}` | 更新消息 |
| DELETE | `/plugin/admin/system-message/delete` | 删除消息 |
| POST | `/plugin/admin/system-message/send` | 发送消息 |
| GET | `/plugin/admin/system-message/statistics` | 获取统计数据 |

### 模板管理接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/plugin/admin/system-message/template/index` | 获取模板列表 |
| GET | `/plugin/admin/system-message/template/read/{id}` | 获取模板详情 |
| POST | `/plugin/admin/system-message/template/save` | 创建模板 |
| PUT | `/plugin/admin/system-message/template/update/{id}` | 更新模板 |
| DELETE | `/plugin/admin/system-message/template/delete` | 删除模板 |
| POST | `/plugin/admin/system-message/template/preview` | 预览模板 |
| POST | `/plugin/admin/system-message/template/copy` | 复制模板 |

### 用户端接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/plugin/api/system-message/user/index` | 获取我的消息 |
| GET | `/plugin/api/system-message/user/read/{id}` | 获取消息详情 |
| PUT | `/plugin/api/system-message/user/markRead/{id}` | 标记已读 |
| PUT | `/plugin/api/system-message/user/markAllRead` | 全部标记已读 |
| DELETE | `/plugin/api/system-message/user/delete/{id}` | 删除消息 |
| GET | `/plugin/api/system-message/user/unreadCount` | 获取未读数量 |
| GET | `/plugin/api/system-message/user/typeStats` | 获取类型统计 |
| GET | `/plugin/api/system-message/user/search` | 搜索消息 |

### 偏好设置接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/plugin/api/system-message/preference/index` | 获取偏好设置 |
| PUT | `/plugin/api/system-message/preference/update` | 更新偏好设置 |
| POST | `/plugin/api/system-message/preference/reset` | 重置为默认 |
| PUT | `/plugin/api/system-message/preference/updateChannels` | 更新渠道偏好 |
| PUT | `/plugin/api/system-message/preference/updateTypes` | 更新类型偏好 |
| PUT | `/plugin/api/system-message/preference/setDoNotDisturbTime` | 设置免打扰时间 |
| PUT | `/plugin/api/system-message/preference/toggleDoNotDisturb` | 开关免打扰 |

## 🗄️ 数据表结构

| 表名 | 说明 |
|------|------|
| `system_messages` | 系统消息主表 |
| `user_messages` | 用户消息关联表 |
| `message_templates` | 消息模板表 |
| `user_notification_preferences` | 用户通知偏好表 |
| `message_delivery_logs` | 消息发送日志表 |


## 🔌 扩展开发

### 自定义消息渠道

如需扩展邮件、短信等渠道，请在 `NotificationService` 中实现对应方法：

```php
// 实现邮件发送
protected function getMailService()
{
    return $this->container->get(YourMailService::class);
}

// 实现短信发送
protected function getSmsService()
{
    return $this->container->get(YourSmsService::class);
}

// 实现推送服务
protected function getPushService()
{
    return $this->container->get(YourPushService::class);
}
```

### 监听消息事件

```php
use Plugin\Since\SystemMessage\Event\MessageSent;
use Plugin\Since\SystemMessage\Event\MessageSendFailed;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class MessageEventListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            MessageSent::class,
            MessageSendFailed::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof MessageSent) {
            // 消息发送成功后的处理
            $message = $event->message;
            // 例如：记录日志、触发其他业务逻辑
        }
        
        if ($event instanceof MessageSendFailed) {
            // 消息发送失败后的处理
            $message = $event->message;
            $error = $event->error;
            // 例如：发送告警、重试等
        }
    }
}
```

### 自定义消息模板类

```php
use Plugin\Since\SystemMessage\Contract\AbstractMessageTemplate;

class OrderShippedTemplate extends AbstractMessageTemplate
{
    protected string $name = 'order_shipped';
    protected string $type = 'system';
    
    public function getTitleTemplate(): string
    {
        return '订单发货通知';
    }
    
    public function getContentTemplate(): string
    {
        return '尊敬的{{username}}，您的订单{{order_no}}已发货，快递单号：{{tracking_no}}';
    }
    
    public function getVariables(): array
    {
        return ['username', 'order_no', 'tracking_no'];
    }
}
```

### 使用辅助函数

```php
// 获取系统消息日志记录器
$logger = system_message_logger();
$logger->info('消息发送成功', ['message_id' => 1]);

// 发送系统消息（辅助函数）
send_system_message('标题', '内容', [1, 2, 3]);

// 使用模板发送
send_template_message(1, [1, 2, 3], ['username' => '张三']);
```

## 🖥️ 前端集成

### 消息通知组件

插件会自动覆盖系统的通知组件，在工具栏显示未读消息数量和消息列表。

### 手动集成消息中心

```vue
<template>
  <MessageCenter />
</template>

<script setup>
import MessageCenter from '@/plugins/since/system-message/views/MessageCenter.vue'
</script>
```

### 使用消息 Store

```typescript
import { useMessageStore } from '@/plugins/since/system-message/store/message'

const messageStore = useMessageStore()

// 获取未读数量
await messageStore.fetchUnreadCount()
console.log(messageStore.unreadCount)

// 获取消息列表
await messageStore.fetchMessages({ page: 1, pageSize: 20 })
console.log(messageStore.messages)

// 标记已读
await messageStore.markAsRead(messageId)

// 标记全部已读
await messageStore.markAllAsRead()
```

## 📋 更新日志

### v1.0.0
- 🎉 首次发布
- ✅ 支持多渠道消息推送（站内信、邮件、短信、推送）
- ✅ 支持消息模板管理
- ✅ 支持用户偏好设置
- ✅ 支持队列异步发送
- ✅ 支持定时消息发送
- ✅ 支持消息优先级
- ✅ 支持免打扰时间设置
- ✅ 提供完整的管理后台界面
- ✅ 提供用户消息中心界面
- ✅ 支持国际化（中文/英文）

## 📞 联系我们

- 作者：Since
- 邮箱：since529393997@gmail.com
