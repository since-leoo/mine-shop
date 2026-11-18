# 统一错误码与中文提示

错误定义位于 `app/Infrastructure/Error`，按业务功能分组。每个枚举 case 有不可复用的六位整数错误码和中文模板；总计 854 项。修改提示只改 `template()`，不在服务或控制器复制中文文案。已发布的错误码不可改号、不可用于另一种错误；新增错误在所属号段追加。

## 调用方式

```php
use App\Infrastructure\Error\CatalogError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

throw CatalogError::PRODUCT_NOT_FOUND->exception();
throw CatalogError::IMAGE_LIMIT_EXCEEDED->invalid([$max]);
throw CatalogError::STOCK_CACHE_MISSING->internal(previous: $error);
throw new BusinessException(CatalogError::PRODUCT_NOT_FOUND, resultCode: ResultCode::NOT_FOUND);
```

模板使用 `sprintf` 占位符，参数按顺序传数组。`message()` 用于已有日志、异步任务失败记录或仍须保留原异常类型的协议适配器；不要把第三方错误全文作为普通业务提示。没有动态参数的错误可直接传给控制器 `error(CatalogError::PRODUCT_NOT_FOUND)`。

`exception()` 返回标准 DomainException，`invalid()` 返回 InvalidArgumentException，`internal()` 返回 RuntimeException，异常 getCode() 为本地错误码；previous 原样保留。事务、队列继续按原异常类型决定是否重试。BusinessException 支持枚举以及原 ResultCode 调用方式，并把响应文案/错误码同步到异常本身，便于日志定位。

## 返回协议

```json
{"code":404,"message":"商品不存在","data":[],"error_code":120001}
```

`code` 仍是原通用响应码，不替换登录处理依赖的 401、403；`error_code` 表示具体业务原因。成功响应不增加该字段。后台 ResponseStruct 增加可选 error_code；移动端请求/上传错误保留为 errorCode，认证判断仍使用 code。

未知数据库、SDK、类型错误以及内部 RuntimeException 在生产环境对外仅返回通用系统错误码 100001 和安全文案，完整信息保留日志。验证器返回其现有字段提示与统一校验错误码；校验规则翻译继续由现有语言资源维护。

异步下单历史失败记录保留现有 public:v1 文案协议，未标记的历史原文仍不对外展示；新产生文案从集中目录取值，不修改历史业务数据。

## 模块号段

| 错误枚举 | 当前已用范围 | 数量 |
| --- | --- | --- |
| AfterSaleError | 260001–260034 | 34 |
| AttachmentError | 150001–150004 | 4 |
| AuthError | 110001–110012 | 12 |
| CartError | 270001–270002 | 2 |
| CatalogError | 120001–120033 | 33 |
| CommerceError | 310001–310038 | 38 |
| CommonError | 100001–100007 | 7 |
| CouponError | 220001–220019 | 19 |
| CustomerServiceError | 500001–500119 | 119 |
| DistributionError | 410001–410040 | 40 |
| DiyError | 510001–510073 | 73 |
| ExportError | 530001–530033 | 33 |
| ExpressError | 560001–560005 | 5 |
| GeoError | 170001–170007 | 7 |
| GroupBuyError | 230001–230029 | 29 |
| InventoryError | 190001–190020 | 20 |
| LogisticsError | 195001–195003 | 3 |
| MemberError | 130001–130027 | 24 |
| OrderError | 200001–200027 | 27 |
| PaymentAdapterError | 320001–320002 | 2 |
| PaymentError | 210001–210026 | 26 |
| PermissionError | 140001–140016 | 16 |
| PluginError | 160001–160023 | 23 |
| ProductReviewError | 420001–420005 | 5 |
| ReviewAccessError | 330001–330004 | 4 |
| SeckillError | 240001–240037 | 37 |
| ShippingError | 250001–250005 | 5 |
| SmsError | 540001–540006 | 6 |
| SupplyChainError | 400001–400151 | 151 |
| SystemMessageError | 520001–520045 | 45 |
| SystemSettingError | 180001–180002 | 2 |
| WechatError | 550001–550003 | 3 |

## 第三方与基础设施边界

供应链保留供应商动态 code 以及 6、429、10001、10002 的协议语义，不能替换为本地编号而破坏重试/对账判断，具体位置见 [供应链错误码说明](../plugins/supply-chain/ERROR-CODES.md)。物流等已有异常类型同样保留，上游上下文仅进入原受控诊断路径。SDK/数据库 catch 后原样重抛无需再分配一个新的本地错误码。

CLI 进度、操作成功提示、日志上下文不属于业务异常目录。HTTP/队列常驻进程上线后需重启以加载新代码；此次未更改业务表结构、权限或历史记录。

## 验证

`tests/Unit/Security/ErrorCodeTest.php` 检查全目录错误码唯一性、模板格式化、异常类型/因果链、响应码兼容和未知异常脱敏。相关插件回归覆盖真实调用点、第三方协议码及控制器返回；执行 `PHP_BIN=/opt/homebrew/opt/php@8.3/bin/php bash tests/run-isolated.sh` 跑隔离后端全套。

本次回归（2026-11-18）：后端单元测试 266 项／5377 断言、隔离集成测试 254 项／3201 断言通过；小程序 25 个测试文件／126 项测试、后台 TypeScript 检查通过；PHPStan level 5（app、config、全部插件 src）0 诊断。变更 PHP 文件语法检查和差异空白检查通过；临时 AST 核对覆盖 943 处可静态解析的枚举方法及 BusinessException 模板调用，未发现参数数量不匹配。未连接真实支付、短信、微信或供应商服务，未操作业务环境数据。
