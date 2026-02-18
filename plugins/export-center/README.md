# 导出中心 (Export Center)

基于 Hyperf 插件架构的统一数据导出管理插件，支持异步导出任务处理、下载中心和系统消息通知。

## 功能

- 异步导出任务处理（基于 Hyperf AsyncQueue）
- 多格式支持：Excel、CSV
- 自动检测 Excel 扩展：优先使用 xlswriter（高性能），fallback 到 PhpSpreadsheet
- DTO 注解驱动：通过 `#[ExportColumn]` 和 `#[ExportSheet]` 注解定义导出列
- 下载中心：查看导出记录、下载文件
- 实时进度跟踪（Redis 缓存）
- 系统消息通知：导出完成/失败自动通知
- 文件生命周期管理：自动清理过期文件（默认 7 天）

## 安装

通过插件管理系统安装，插件会自动：

1. 发布数据库迁移文件
2. 发布导出配置文件

## 配置

安装后配置文件位于 `config/autoload/export.php`，可配置：

- `storage_path` - 导出文件存储路径
- `expire_days` - 文件过期天数（默认 7 天）
- `max_file_size` - 单文件最大大小（默认 100MB）
- `max_retry_count` - 最大重试次数（默认 3 次）

## 使用方式

### 1. 定义导出 DTO

```php
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(name: '订单数据', freezeHeader: true)]
class OrderExportDto
{
    #[ExportColumn(title: '订单编号', order: 1, width: 20)]
    public string $orderNo;

    #[ExportColumn(title: '金额', type: 'float', order: 2, width: 12, align: 'right')]
    public float $amount;

    #[ExportColumn(title: '创建时间', type: 'date', order: 3, format: 'Y-m-d H:i:s')]
    public string $createdAt;

    /**
     * 提供导出数据（必须实现此静态方法）
     */
    public static function getData(array $params): iterable
    {
        // 返回数据迭代器
        return Order::query()->where(...)->cursor();
    }
}
```

### 2. 创建导出任务

```php
use Plugin\ExportCenter\Dto\ExportTaskDto;
use Plugin\ExportCenter\Service\ExportService;

$exportService = make(ExportService::class);

$task = $exportService->createTask(new ExportTaskDto(
    userId: $currentUserId,
    taskName: '订单导出',
    dtoClass: OrderExportDto::class,
    exportFormat: 'excel',
    exportParams: ['start_date' => '2024-01-01'],
));
```

## API 端点

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/admin/export/tasks` | 创建导出任务 |
| GET | `/admin/export/tasks` | 查询任务列表 |
| GET | `/admin/export/tasks/{id}` | 查询任务详情 |
| GET | `/admin/export/tasks/{id}/download` | 下载导出文件 |
| DELETE | `/admin/export/tasks/{id}` | 删除导出任务 |
| GET | `/admin/export/tasks/{id}/progress` | 获取任务进度 |
