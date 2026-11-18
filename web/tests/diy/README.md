# DIY 插件浏览器回归

从 `web` 目录运行：

```sh
node node_modules/@playwright/test/cli.js test -c tests/diy/playwright.config.ts
```

测试自行启动独立的 `127.0.0.1:4001` Vite 服务，用完自动关闭；端口占用会报错，不复用或停止已有服务。所有 `/admin/` API 在浏览器层模拟，外部请求被阻止，不使用业务账号或数据库。失败时保留 trace 到 `/tmp/mine-diy-playwright`。

测试运行真实 Vue Router、Pinia、`usePluginStore.registerPlugin` 和 `useRouteStore.initRoutes`，从 `web/src/plugins/since/diy/index.ts` 加载实际的外部插件 `plugins/diy/web`。装修列表、表格动作、插件中心、可用性校验与编辑器均是产品组件，fixture **不声明 DIY 路由，也不替换插件注册逻辑**。这样缺失路由会真正命中产品路由表的 404。

为隔离应用外壳，fixture 只简化 Layout/404 的外观、HTTP 传输、会员权限数据来源、翻译、图标及本轮不涉及的元数据弹窗/图片上传控件；`hasAuth` 权限判断、所有 Element Plus 和 MineAdmin 表格/表单组件使用实际实现。此回归不替代真实后端权限、草稿持久化和上传测试。

共 8 项：中心卡片进入装修列表并刷新、进入编辑/刷新/返回列表与中心、直达编辑并提交真实草稿 payload、旧 editor/page URL 兼容、停用与重新启用插件、同一 Router 切换页面 ID 时加载前禁止保存且不串草稿、无 update 权限隐藏按钮并拦截直达编辑、发布等待草稿保存期间切页仍发布原页面。原实现已出现真实红灯：列表可见，点击“装修”后仅显示 404，编辑器不存在；红灯报告保留在 `/tmp/mine-diy-before-red`。

设置 `DIY_SCREENSHOT=/absolute/path.png` 可在草稿保存成功后导出一张编辑器截图。
