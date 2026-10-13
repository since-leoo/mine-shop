# 客服 V2

客服插件包含管理工作台、三级问题类目、关键词识别、人工技能与优先级分配、可配置 AI 接待、FAQ 发布版本以及小程序聊天。AI 默认关闭；未启用时保持人工接待。旧客户端未传 `protocol_version=2` 时使用人工接待，恢复旧会话不会产生第二条活动会话。

## 配置与数据

管理端 `/admin/customer-service/configuration` 管理草稿、发布和回滚。`categories`、`keyword_rules`、`skill_groups`、`agent_skills`、`ai_connections`、`ai_agents` 各有独立 CRUD 接口，编辑同一配置草稿；保存必须携带 `expected_revision`，发生并发修改时拒绝覆盖。

这些定义物理存放在 `customer_service_config_revisions.snapshot_json`，`customer_service_config_state` 保存草稿和发布指针。这样一次发布的类目、规则、技能与 AI 引用始终一致。定义数量上限每类 2000 条，适合后台配置规模；不是每个定义一张关系表。稳定 ID、引用、层级、循环、字段类型由领域服务校验；`_next_ids` 保持独立 CRUD 新增编号单调递增。回滚生成新版本，历史版本不可修改。

会话、消息、AI 运行、事件 outbox、FAQ 版本及评价各使用独立表。全新部署只需执行原始两份客服迁移：

- `2026_08_21_000001_create_customer_service_tables.php`：坐席、完整会话状态、消息、FAQ、知识发布版本和配置快照/指针。
- `2026_08_21_000002_create_customer_service_audit_tables.php`：人工接待记录、会话日志、AI 任务、outbox 和评价。

12 张表及所有字段（含主键和时间）均带中文备注。`reception_state` 等运行字段直接在建表时创建；不再保留客服 V2 升级迁移，不做旧会话、FAQ、角色授权的历史补数据。配置指针仅初始化一行单例记录，AI 默认关闭。

客服权限由原始 `seeders/menu_seeder_20240926.php` 初始化，包含查看、接待、回复、转接、关闭、全部会话管理、配置管理及 FAQ 权限；角色授权由管理员明确配置，不根据其他权限自动补授。插件页面在插件中心展示，权限分组本身不生成重复路由。

部署顺序为标准 `php bin/hyperf.php migrate`、`php bin/hyperf.php db:seed`，然后启动服务。此次结构针对全新数据库；已经执行过旧建表迁移的数据库不会因为文件修改而自动补列，不能把修改迁移文件视为线上升级。没有类目或技能体系时采用通用分配；配置后坐席必须绑定有效技能才接单。系统设置中的开关、容量和网关仍作为未发布配置时的默认来源。

## 接待规则

- 类目最多三级；祖先停用使子级失效，祖先强制人工规则不能被子级弱化。
- 关键词支持 exact / contains / any / all、排除词、渠道与优先级；同优先级冲突不擅自选一类。人工确认的分类保持优先。
- 人工分配先匹配类目技能组及其兜底链；AI 转人工时按类目显式技能组、Agent 兜底组、全局兜底组顺序选择。没有有效技能的坐席不会越组接单。
- 匹配候选还检查启用状态、在线租约、容量、班次，按技能优先级、坐席优先级，再按最少负载比例 / 轮询 / 权重分配。停用技能及技能组同时影响查询权限、手工接单和自动分配。
- 未接单预占发生分类改变时释放原坐席容量并重新路由；已接单会话保留当前人工，避免谈话中途自动换人。
- 关闭排队仍会尝试在线客服；无人匹配才进入留言状态。营业外、满队列或超时转留言。人工可接管留言；营业时间恢复后自动重试营业外留言。

HTTP 与 WebSocket 调用相同应用命令。事务内同时保存消息与 outbox，提交后投递；消息按发送人和 `client_message_id` 幂等，序号用于 `after_sequence` 断线补拉。WebSocket ACK 不重复广播。Outbox 至少投递一次，客户端按事件 / 消息 ID 去重；内部消息不会推给会员。跨实例票据与在线状态由 Redis 接口实现。

## AI 运行

`AiProviderInterface` 可替换供应商；默认适配 HTTPS Chat Completions 兼容协议。凭据只允许引用服务端 `CUSTOMER_SERVICE_AI_*_KEY` 环境变量，不能保存或返回明文密钥。服务器另需配置精确主机允许列表，例如：

```dotenv
CUSTOMER_SERVICE_AI_ALLOWED_HOSTS=ai.example.com
CUSTOMER_SERVICE_AI_PRIMARY_KEY=your-server-secret
```

需要 PHP cURL 扩展。连接使用 TLS、禁用重定向和代理，并把经过公网检查的 DNS 地址绑定到请求，避免解析后地址变化。修改这些环境变量后需要重启服务进程。

开启 AI 必须配置可用连接和默认 Agent。Agent 可设置模型、角色说明、知识范围、检索阈值、输出上限、只读工具和人工兜底组。用户消息先持久化，定时 Worker 异步检索和调用；AI 无证据、拒答、超时、异常、超预算或达澄清上限时进入人工流程。用户主动转人工、旧客户端恢复、Agent 下线及人工接管均使迟到 AI 回复失效。

FAQ 草稿与发布答案隔离，可设置生效和失效时间、相似问法。AI 只引用本次提供的证据，缺失或伪造引用会转人工。知识检索目前使用中文双字相似度，最多搜索 1000 个已发布 FAQ，返回前三条；未接入向量库。内置工具仅支持 `order_status`，以当前认证会员身份查询本人订单，仅输出状态字段，没有退款、改价或其他写操作。Agent 沙盒试问禁用会员工具，不写入聊天会话。

自动接待的每日 Token 预算通过行锁预留本次输入上界和输出限额；有供应商 usage 时归还未用额度，缺 usage / 调用结果不明确时保守计入预留量。预算按服务端自然日统计，部署节点应统一时区。AI 默认关闭，测试使用 fake provider，未调用真实外部收费服务。

## 运行与验证

Hyperf Crontab 必须启用（仓库默认已启用）。`customer-service-worker` 每 5 秒负责 AI、排队补偿和 outbox 投递；`CustomerServiceEventSubscriber` 负责跨实例通知。Redis 不可用时不能把进程内状态当作跨实例在线依据。

服务端消息长度、图片大小、图片/商品开关均检查；图片还验证为当前会员上传的附件。图片大小同时受上传通道自身上限约束。完整 UI 使用 `plugins/customer-service/web` 单一源，宿主 `web/src/plugins/since/customer-service` 引用它。

运行全部隔离验证（自动启动/停止临时 MySQL、Redis，不读取业务 `.env`）：

```sh
bash tests/run-isolated.sh
```

仅运行客服规则与实际数据库测试（socket 只能指向独立测试目录，`DatabaseTestCase` 会拒绝其他路径；以下目录名需替换为实际测试目录）：

```sh
php vendor/bin/phpunit -c phpunit.unit.xml tests/Unit/CustomerService
MINE_SHOP_TEST_MYSQL_SOCKET=/tmp/mine-shop-tests.自建测试目录/mysql.sock php vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/CustomerService
```

测试覆盖规则、Request/HTTP 权限、真实配置发布并发、全新建库字段/索引/备注与回滚重建、消息幂等、跨进程活动会话/坐席容量争抢、异步 AI 与人工竞态、预算并发及恢复、outbox 失败重试、类目重路由、技能禁用、FAQ 草稿隔离。传输与真实 Redis 测试另在 `CustomerServiceTransport` 目录。真实供应商质量、延迟与实际部署网关需使用运营环境配置验证。
