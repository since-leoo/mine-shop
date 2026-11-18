运行：`pnpm exec playwright test --config tests/export-download/playwright.config.ts`（Node 20）。

隔离 Vite 页面执行两个导出插件镜像的真实下载按钮、API 和通用 Axios 拦截器，仅替换用户/插件状态并拦截 HTTP，不连接真实后端。覆盖带令牌的 CSV 下载、中文文件名、业务/HTTP 401 与403、损坏或成功码JSON、网络失败、令牌刷新成功、并发刷新失败及重试上限。
