# 企业微信 SCRM 插件

插件提供企业微信基础连接、客户标签/标签组、群 SOP、系统用户绑定和主应用事件监听能力。核心直接调用企业微信官方 REST API，不依赖第三方企业微信 SDK。

## 安装

通过 MineAdmin 插件管理器安装 `since/wecom-scrm`。插件会执行迁移并把企业微信设置合并到 `config/autoload/mall.php`。

## 配置

在系统设置的“企业微信 SCRM”分组填写 CorpID、Secret、回调 Token 和 EncodingAESKey。配置以 `mall.wecom.*` 保存，插件卸载时会恢复安装前的 `mall.php` 备份。

## 企业微信回调

在企业微信管理后台将通讯录与客户标签事件回调地址设置为：`/wecom-scrm/callback`（GET 用于 URL 校验，POST 接收事件）。回调接口会校验签名并异步投递同步任务：`change_external_tag` 刷新标签组/标签，`change_contact` 的成员或部门变更刷新员工通讯录。部门事件当前复用通讯录同步，后续可在 `WecomCallbackService` 中扩展独立部门持久化。

## 解耦事件

插件监听 `MemberRegistered` 和 `OrderPaidForMember`。主应用无需引用企业微信类；标签、绑定和 SOP 规则均在插件内部扩展。新增事件监听器时放在 `src/Listener` 并使用 Hyperf `#[Listener]` 注解。

核心采用 AccessToken 管理器（缓存单例语义）、统一 HTTP 客户端和能力路由，后续可按策略扩展通讯录、客户联系、客户群、应用消息、机器人和卡片接口。

当前能力 Handler：

- `TagHandler`：企业客户标签和标签组
- `CustomerHandler`：外部联系人查询、备注
- `GroupChatHandler`：客户群查询、群消息
- `MessageHandler`：自建应用文本、Markdown 消息
