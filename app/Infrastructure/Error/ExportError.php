<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum ExportError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case OPERATOR_DISABLED = 530001;
    case DYNAMIC_SCOPE_UNSUPPORTED = 530002;
    case DTO_COLUMNS_MISSING = 530003;
    case PRIVATE_DIRECTORY_CREATE_FAILED = 530004;
    case PRIVATE_FILE_SAVE_FAILED = 530005;
    case FILE_PERMISSIONS_FAILED = 530006;
    case LEGACY_FILE_DISABLED = 530007;
    case DATA_SCOPE_CHANGED = 530008;
    case FILE_UNAVAILABLE = 530009;
    case PRIVATE_DIRECTORY_REQUIRED = 530010;
    case OPERATOR_UNAVAILABLE = 530011;
    case TASK_NOT_FOUND = 530012;
    case TASK_RETRY_FORBIDDEN = 530013;
    case TASK_NOT_RETRYABLE = 530014;
    case TASK_DELETE_FORBIDDEN = 530015;
    case PROCESSING_TASK_DELETE_FORBIDDEN = 530016;
    case TASK_ACCESS_FORBIDDEN = 530017;
    case FILE_DOWNLOAD_FORBIDDEN = 530018;
    case TASK_INCOMPLETE = 530019;
    case FILE_MISSING_OR_EXPIRED = 530020;
    case FILE_EXPIRED = 530021;
    case OPERATOR_NOT_FOUND = 530022;
    case FILE_REEXPORT_REQUIRED = 530023;
    case QUEUE_REJECTED = 530024;
    case TASK_ID_NOT_FOUND = 530025;
    case TASK_START_STATE_INVALID = 530026;
    case DTO_PROVIDER_MISSING = 530027;
    case TEMP_DIRECTORY_CREATE_FAILED = 530028;
    case DATA_SCOPE_CHANGED_DURING_EXPORT = 530029;
    case OUTPUT_FILE_MISSING = 530030;
    case ZIP_CREATE_FAILED = 530031;

    case QUEUE_SUBMISSION_FAILED = 530032;

    case PROCESSING_FAILED = 530033;

    public function template(): string
    {
        return match ($this) {
            self::OPERATOR_DISABLED => '导出任务操作者已停用',
            self::DYNAMIC_SCOPE_UNSUPPORTED => '动态自定义数据权限暂不支持持久化导出，请使用明确的数据范围策略',
            self::DTO_COLUMNS_MISSING => 'DTO 类 %s 未定义任何 #[ExportColumn] 注解',
            self::PRIVATE_DIRECTORY_CREATE_FAILED => '无法创建私有导出目录',
            self::PRIVATE_FILE_SAVE_FAILED => '无法保存私有导出文件',
            self::FILE_PERMISSIONS_FAILED => '无法设置导出文件访问权限',
            self::LEGACY_FILE_DISABLED => '旧版导出文件已停用，请重新导出',
            self::DATA_SCOPE_CHANGED => '数据权限已变更，请重新导出',
            self::FILE_UNAVAILABLE => '导出文件不可用，请重新导出',
            self::PRIVATE_DIRECTORY_REQUIRED => '导出目录必须位于非公开存储路径',
            self::OPERATOR_UNAVAILABLE => '导出任务操作者不存在或已停用',
            self::TASK_NOT_FOUND => '导出任务不存在',
            self::TASK_RETRY_FORBIDDEN => '无权限重试此任务',
            self::TASK_NOT_RETRYABLE => '该任务无法重试',
            self::TASK_DELETE_FORBIDDEN => '无权限删除此任务',
            self::PROCESSING_TASK_DELETE_FORBIDDEN => '正在处理的任务无法删除',
            self::TASK_ACCESS_FORBIDDEN => '无权限访问此任务',
            self::FILE_DOWNLOAD_FORBIDDEN => '无权限下载此文件',
            self::TASK_INCOMPLETE => '该任务尚未完成导出',
            self::FILE_MISSING_OR_EXPIRED => '文件不存在或已过期',
            self::FILE_EXPIRED => '文件已过期，请重新导出',
            self::OPERATOR_NOT_FOUND => '导出任务操作者不存在',
            self::FILE_REEXPORT_REQUIRED => '文件不存在或已过期，请重新导出',
            self::QUEUE_REJECTED => '导出队列拒绝接收任务',
            self::TASK_ID_NOT_FOUND => '导出任务不存在: %s',
            self::TASK_START_STATE_INVALID => '只有待处理状态的任务才能开始处理，当前状态: %s',
            self::DTO_PROVIDER_MISSING => 'DTO %s 未配置 dataProvider',
            self::TEMP_DIRECTORY_CREATE_FAILED => '无法创建导出临时目录',
            self::DATA_SCOPE_CHANGED_DURING_EXPORT => '导出期间数据权限已变更，请重新导出',
            self::OUTPUT_FILE_MISSING => '导出未生成文件',
            self::ZIP_CREATE_FAILED => '无法创建 zip 文件',
            self::QUEUE_SUBMISSION_FAILED => '导出任务暂时无法提交，请稍后重试',
            self::PROCESSING_FAILED => '导出处理失败，请联系管理员查看日志',
        };
    }
}
