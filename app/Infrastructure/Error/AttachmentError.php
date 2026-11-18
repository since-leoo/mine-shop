<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum AttachmentError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case FILE_REQUIRED = 150001;
    case IMAGE_FORMAT_UNSUPPORTED = 150002;
    case IMAGE_TOO_LARGE = 150003;
    case NOT_FOUND = 150004;

    public function template(): string
    {
        return match ($this) {
            self::FILE_REQUIRED => '请选择要上传的文件',
            self::IMAGE_FORMAT_UNSUPPORTED => '仅支持 jpg/png/gif/webp 格式图片',
            self::IMAGE_TOO_LARGE => '图片大小不能超过 5MB',
            self::NOT_FOUND => '附件不存在',
        };
    }
}
