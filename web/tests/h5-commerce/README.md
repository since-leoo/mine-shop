# H5 地址与确认订单回归

从 `web` 目录运行，建议使用不受开发 watch 清理影响的独立 H5 构建目录：

```sh
H5_DIST=/absolute/h5/build node node_modules/@playwright/test/cli.js test -c tests/h5-commerce/playwright.config.ts
```

使用真实 Taro H5 编译产物与路由，独立 Vite preview 端口 `4002`，测试结束自动关闭。`H5_ARTIFACTS` 可指定失败截图和 trace 输出目录（默认 `/tmp/mine-h5-commerce-playwright`）。不会复用或停止用户已有服务。

设置 `H5_COMMERCE_SCREENSHOTS=/absolute/output/directory` 可在通过的用例中导出 375px 典型地址列表/编辑/确认页，以及 320px 长姓名与大金额页面，供视觉检查。

通过 Taro 实际 `{data: ...}` 存储格式写入模拟会员/token；所有 API 均在浏览器网络层拦截，未声明接口会使断言失败，外部资源请求被阻止。页面、表单、导航、缓存消费、优惠选择、Price 展示和请求 camelCase→snake_case 转换均使用实际产品代码。不会登录业务账号、创建真实订单、访问业务库或发起支付。

6 类场景 × 320 / 375 / 390 / 430px，共 24 项：

- 长收件人/详细地址、默认标记单行居中、卡片宽度和触摸区域。
- 选择模式下编辑/删除不误选；编辑字段真实提交、删除确认及列表刷新。
- 地址选择返回确认页，消费选中缓存、重新预览并正确提交商品/收货信息。
- 自动优惠后进入真实优惠券页切换优惠券，核验价格、优惠和提交 payload 一致。
- 大金额、长商品标题/规格下总价与提交按钮不重叠、不横向溢出。
- Chromium CDP 设置真实 34px `safe-area-inset-bottom`，验证按钮避开安全区、底部内容可以滚动到固定栏上方且按钮可点击；不注入产品样式。

原构建已实际复现默认标签折成两行、320px 大金额溢出；优惠券可用时还暴露初始化依赖循环导致持续预览请求。旧截图/trace 分别保存于 `/tmp/mine-h5-commerce-before` 与 `/tmp/mine-h5-commerce-before-functional`。编辑页导航带有横向滑入动画，测试会等待边界进入视口再测量，避免把过渡态误判为页面溢出。

这些用例验证前端真实交互和请求契约；不替代后端价格计算、订单事务与支付集成测试。
