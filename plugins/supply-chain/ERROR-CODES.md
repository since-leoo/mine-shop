# 供应链异常与协议错误码

应用自有中文异常统一维护在 `app/Infrastructure/Error/SupplyChainError.php`，使用 `400xxx` 号段。调用 `exception()`、`invalid()`、`internal()` 分别保持原来的 `DomainException`、`InvalidArgumentException`、`RuntimeException` 类型。带字段名、批次上限、HTTP 状态或请求标识的内容通过模板参数格式化。

下列错误码参与已发送/未发送判断、限流、Token 刷新和查单恢复，**不得替换为 `400xxx`**。这些位置仅集中管理文案，仍用原异常类和原协议错误码：

| 错误码 | 含义 | 保留位置（均位于 `src/`） |
| --- | --- | --- |
| 供应商 `code`，包括 `6` | 上游拒绝；`6` 仅允许读请求刷新 Token 后重试一次，写请求不自动重放 | `Infrastructure/Sdk/Yunzhong/YunzhongClient.php` |
| `429` | 连接限流、Token 刷新竞争、请求未发送 | `Infrastructure/Sdk/RedisRequestLimiter.php`、`Infrastructure/Sdk/Yunzhong/YunzhongClient.php` |
| `10002` / `AUDIT_UNAVAILABLE` | 前置校验、意图日志、库存保护或发送前租约失败；请求未发送 | `Application/Commad/AppSupplyWorkflow.php`、`Domain/Service/DomainSupplyWorkflowService.php`、`Infrastructure/Sdk/Yunzhong/YunzhongClient.php` |
| `10001` / `OUTCOME_UNKNOWN` | 写请求结果不确定，先查单，禁止直接重新采购或重复售后 | `Application/Commad/AppSupplyWorkflow.php`、`Domain/Service/DomainSupplyAfterSaleService.php`、`Domain/Entity/SupplyAfterSaleEntity.php`、`Handler/Yunzhong/{OrderHandler,AfterSaleHandler}.php`、`Infrastructure/Sdk/Yunzhong/YunzhongClient.php` |

供应商动态 `msg` 经现有脱敏后保留在协议异常中，仍沿原 DomainException 路径返回该拒绝提示，不注册为本地六位业务码。未知网络或响应异常保留 RuntimeException，生产环境对外返回通用系统错误，不能仅凭错误码数值把它当作可公开的业务异常。传输异常仍不串联可能携带凭证 URL 的原始异常。

`Service/ConnectionConfig.php` 禁止序列化凭证配置，保持 `LogicException` 类型，文案和本地错误码取自集中目录。

回归入口：`tests/Unit/SupplyChain` 覆盖 Token 读重试/写不重放、限流、审计失败、未知结果以及参数化异常；`tests/Integration/SupplyChain` 覆盖持久化、库存保护、采购和售后恢复。测试使用隔离数据库或 Mock HTTP，不访问真实供应商。
