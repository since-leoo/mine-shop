# 插件顶部标签集成回归

从 `web` 目录执行：

```sh
node node_modules/@playwright/test/cli.js test -c tests/plugin-tabs/playwright.config.ts
```

测试启动独立 Vite 页面（`127.0.0.1:4028`），使用真实的插件中心、Tabbar、TabStore、RouteStore、PluginStore、KeepAliveStore、Vue Router、WebStorageCache 和右键菜单。插件卡片、路由地址、路由参数、查询参数、标签点击、关闭、刷新和拖拽均通过浏览器执行。断言读取 store 快照，不用替身模拟标签行为。

替身范围仅限业务内容页、插件声明内容和主题设置；菜单转换、插件 `views`、插件 `registerRoute` hook 注册均经过真实注册代码。布局保留项目实际 `route.fullPath` 的组件 key 和 KeepAlive 显隐刷新机制。旧缓存通过真实缓存 API 写入，随后由实际 `initTab` / `normalizeTabs` 归并。

覆盖：

- 插件总览、卡片、不同插件、查询参数子页、返回只占一个“插件中心”标签。
- 切出再切回及浏览器重载恢复最后一个具体 URL。
- 旧插件缓存归并，保留固定状态，当前地址优先；非插件页启动时保留旧缓存的最后插件地址。
- 普通页面相同 route name、相同 path 的不同参数和 query 仍拥有独立标签。
- 旧菜单插件路径、主模块兼容入口、插件声明及嵌套路由、hook 和静态工作台均有分组元数据。
- 关闭当前、关闭左侧/右侧/其它、固定、刷新重挂载以及完整插件组组件缓存清理。
- 关闭导致标签数组替换后，真实拖拽仍更新 store 并持久化次序。

不访问业务 API、真实供应链服务或数据库；非本地网络请求被测试拦截。测试产物保存在 `/tmp/mine-plugin-tabs-playwright`。
