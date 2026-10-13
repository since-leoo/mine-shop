# 客服前端

后台实现唯一保存在 `plugins/customer-service/web`，`web/src/plugins/since/customer-service` 仅保留兼容转发。小程序实现保存在其 sourceRoot 内的 `miniprogram/src/plugins/customer-service`，原页面和 service 路径保留转发。

插件中心的「接待配置」及设置入口都使用新配置版本接口。管理员可编辑接待开关、AI 优先接待、营业时间、排队时限、三级类目、关键词、技能组、坐席技能、模型连接及 AI Agent；修改先进入本地草稿，再保存或发布。服务器版本冲突不会覆盖本地草稿，刷新前会提示。坐席的优先级、权重、容量与排班独立保存并立即生效。

AI 密钥只配置 `CUSTOMER_SERVICE_AI_*_KEY` 环境变量名，域名需在服务端 `CUSTOMER_SERVICE_AI_ALLOWED_HOSTS` 内。试问按钮明确调用供应商并消耗额度，使用沙盒接口，不给客户发消息。FAQ 需显式发布并在有效期内才能被 AI 引用。

工作台须具备 `customer-service:conversation:reply` 且正在接待自己的会话才允许回复。工作台本轮支持文本和 FAQ 插入，能查看用户图片、商品卡；原有后台图片/商品发送占位入口继续禁用。小程序保留图片上传、FAQ/商品消息展示并支持商品详情跳转。

两端收到 Socket `authenticated` 才显示连接成功，每 25 秒心跳。重连换取新票据，按持久 `sequence` 分页补拉；Socket 乱序事件不会推进 HTTP 游标。消息以 `id` / `client_message_id` 去重，文本用 HTTP 持久化响应确认，失败重试复用幂等 ID，不把 ACK 视为送达。小程序支持用户主动转人工、选择问题类目、结束及服务评价。

验证命令（仓库根目录分别切换到对应项目后执行）：

```sh
cd web
node node_modules/@playwright/test/cli.js install chromium --only-shell
node node_modules/@playwright/test/cli.js test -c tests/customer-service/playwright.config.ts
node node_modules/vite/bin/vite.js build
```

```sh
cd miniprogram
node node_modules/vitest/vitest.mjs run --configLoader native --pool threads src/plugins/customer-service
node node_modules/typescript/bin/tsc --noEmit
node node_modules/@tarojs/cli/bin/taro build --type weapp
```

组件测试运行实际 Vue/React 页面，HTTP/Socket 仅在测试适配层模拟，不连接业务数据库、不调用真实模型。线上网关连接、供应商调用需部署配置后做环境验收。
