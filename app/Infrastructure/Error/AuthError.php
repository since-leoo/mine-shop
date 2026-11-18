<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum AuthError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ACCOUNT_DISABLED = 110001;
    case OLD_PASSWORD_INCORRECT = 110002;
    case UNAUTHORIZED = 110003;
    case INVALID_TOKEN = 110004;
    case LOGIN_REQUIRED = 110005;
    case SIGNATURE_HEADERS_INVALID = 110006;
    case SIGNATURE_EXPIRED = 110007;
    case CLIENT_UNSUPPORTED = 110008;
    case BODY_HASH_MISMATCH = 110009;
    case SIGNATURE_INVALID = 110010;
    case NONCE_REUSED = 110011;
    case PASSWORD_INCORRECT = 110012;

    public function template(): string
    {
        return match ($this) {
            self::ACCOUNT_DISABLED => '账号已停用',
            self::OLD_PASSWORD_INCORRECT => '旧密码错误',
            self::UNAUTHORIZED => '未授权',
            self::INVALID_TOKEN => '令牌非法',
            self::LOGIN_REQUIRED => '请先登录',
            self::SIGNATURE_HEADERS_INVALID => 'API 签名请求头无效',
            self::SIGNATURE_EXPIRED => 'API 签名时间戳已过期',
            self::CLIENT_UNSUPPORTED => '不支持的 API 客户端',
            self::BODY_HASH_MISMATCH => 'API 请求内容校验失败',
            self::SIGNATURE_INVALID => 'API 签名无效',
            self::NONCE_REUSED => 'API 请求已重复提交',
            self::PASSWORD_INCORRECT => '密码错误',
        };
    }
}
