<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum GeoError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case SOURCE_URL_MISSING = 170001;
    case SOURCE_REQUEST_FAILED = 170002;
    case PAYLOAD_FORMAT_INVALID = 170003;
    case CSV_EMPTY = 170004;
    case CSV_HEADER_INVALID = 170005;
    case VERSION_EXISTS = 170006;
    case NOT_SYNCHRONIZED = 170007;

    public function template(): string
    {
        return match ($this) {
            self::SOURCE_URL_MISSING => '未配置可用的行政区划数据源',
            self::SOURCE_REQUEST_FAILED => '行政区划数据源请求失败，HTTP %s',
            self::PAYLOAD_FORMAT_INVALID => '行政区划数据格式无效，需要 JSON 数组',
            self::CSV_EMPTY => '行政区划 CSV 数据为空',
            self::CSV_HEADER_INVALID => '行政区划 CSV 表头格式无效',
            self::VERSION_EXISTS => '行政区划版本 %s 已存在，请使用 --force 覆盖',
            self::NOT_SYNCHRONIZED => '尚未同步行政区划数据',
        };
    }
}
