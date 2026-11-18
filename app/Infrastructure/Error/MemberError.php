<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum MemberError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ADDRESS_NOT_FOUND = 130001;
    case NOT_FOUND = 130002;
    case WALLET_NOT_FOUND = 130003;
    case BALANCE_CHANGED = 130004;
    case STATUS_INVALID = 130005;
    case CREDENTIALS_INVALID = 130006;
    case DISABLED = 130007;
    case PHONE_REQUIRED = 130008;
    case REFUND_AMOUNT_INVALID = 130009;
    case BALANCE_CHANGE_ZERO = 130010;
    case BALANCE_INSUFFICIENT = 130011;
    case WECHAT_OPENID_MISSING = 130012;
    case AUTHORIZATION_FAILED = 130013;
    case PHONE_FETCH_FAILED = 130014;
    case ID_NOT_FOUND = 130015;
    case PHONE_ALREADY_REGISTERED = 130018;
    case VERIFICATION_CODE_INVALID = 130019;
    case LEVEL_NOT_FOUND = 130021;
    case LEVEL_NUMBER_DUPLICATED = 130022;
    case LEVEL_THRESHOLD_INVALID = 130023;
    case LEVEL_NAME_DUPLICATED = 130024;
    case LEVEL_NAME_REQUIRED = 130025;
    case LEVEL_CONFIG_INCOMPLETE = 130026;
    case LEVEL_CONFIG_INVALID = 130027;

    public function template(): string
    {
        return match ($this) {
            self::ADDRESS_NOT_FOUND => '地址不存在',
            self::NOT_FOUND => '会员不存在',
            self::WALLET_NOT_FOUND => '钱包不存在',
            self::BALANCE_CHANGED => '钱包余额已变化，请重试',
            self::STATUS_INVALID => '会员状态无效',
            self::CREDENTIALS_INVALID => '手机号或密码错误',
            self::DISABLED => '会员账号已停用',
            self::PHONE_REQUIRED => '手机号不能为空',
            self::REFUND_AMOUNT_INVALID => '退款金额必须大于 0',
            self::BALANCE_CHANGE_ZERO => '变动值不能为 0',
            self::BALANCE_INSUFFICIENT => '余额不足，当前余额：%d',
            self::WECHAT_OPENID_MISSING => '微信登录返回的 openid 为空',
            self::AUTHORIZATION_FAILED => '授权失败',
            self::PHONE_FETCH_FAILED => '获取手机号失败',
            self::ID_NOT_FOUND => '会员不存在: ID=%s',
            self::PHONE_ALREADY_REGISTERED => '该手机号已注册',
            self::VERIFICATION_CODE_INVALID => '验证码错误或已失效',
            self::LEVEL_NOT_FOUND => '会员等级不存在',
            self::LEVEL_NUMBER_DUPLICATED => '等级序号不能重复',
            self::LEVEL_THRESHOLD_INVALID => '等级 %d 的成长值门槛必须大于等级 %d 的成长值门槛',
            self::LEVEL_NAME_DUPLICATED => '会员等级名称不能重复',
            self::LEVEL_NAME_REQUIRED => '会员等级名称不能为空',
            self::LEVEL_CONFIG_INCOMPLETE => '会员等级配置字段不完整',
            self::LEVEL_CONFIG_INVALID => '会员等级配置字段不合法',
        };
    }
}
