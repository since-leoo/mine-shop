<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum SystemMessageError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case METHOD_NOT_FOUND = 520001;
    case TEMPLATE_NOT_FOUND = 520002;
    case MESSAGE_NOT_VISIBLE = 520003;
    case RECEIPT_NOT_VISIBLE = 520004;
    case MESSAGE_NOT_FOUND = 520005;
    case MESSAGE_NOT_SENDABLE = 520006;
    case RECIPIENTS_NOT_FOUND = 520007;
    case FIELD_REQUIRED = 520008;
    case TITLE_TOO_LONG = 520009;
    case CONTENT_TOO_LONG = 520010;
    case MESSAGE_TYPE_INVALID = 520011;
    case RECIPIENT_TYPE_INVALID = 520012;
    case PRIORITY_INVALID = 520013;
    case QUEUE_REJECTED = 520014;
    case CHANNEL_UNCONFIRMED = 520015;
    case CHANNEL_UNSUPPORTED = 520016;
    case MINIAPP_SINGLE_RECIPIENT_REQUIRED = 520017;
    case MINIAPP_RECEIPT_FAILED = 520018;
    case TEMPLATE_IN_USE = 520019;
    case TEMPLATE_NAME_TOO_LONG = 520020;
    case TEMPLATE_NAME_DUPLICATE = 520021;
    case TEMPLATE_TYPE_INVALID = 520022;
    case TEMPLATE_VARIABLE_INVALID = 520023;
    case TEMPLATE_SYNTAX_INVALID = 520024;
    case EMAIL_ADDRESS_INVALID = 520025;
    case EMAIL_SUBJECT_INVALID = 520026;
    case EMAIL_TRANSPORT_REJECTED = 520027;
    case GATEWAY_CHANNEL_UNKNOWN = 520028;
    case GATEWAY_CONFIGURATION_INVALID = 520029;
    case GATEWAY_RECEIPT_FAILED = 520030;

    case MESSAGE_RECORD_NOT_FOUND = 520031;
    case MESSAGE_DELETE_SELECTION_REQUIRED = 520032;
    case MESSAGE_ID_REQUIRED = 520033;
    case MESSAGE_SCHEDULE_INPUT_REQUIRED = 520034;
    case MESSAGE_SEND_SELECTION_REQUIRED = 520035;
    case SEARCH_KEYWORD_REQUIRED = 520036;
    case TEMPLATE_RECORD_NOT_FOUND = 520037;
    case TEMPLATE_DELETE_SELECTION_REQUIRED = 520038;
    case TEMPLATE_ID_REQUIRED = 520039;
    case TEMPLATE_DATA_REQUIRED = 520040;
    case MESSAGE_NOT_FOUND_OR_READ = 520041;
    case MESSAGE_MARK_SELECTION_REQUIRED = 520042;
    case CHANNEL_SETTINGS_REQUIRED = 520043;
    case TYPE_SETTINGS_REQUIRED = 520044;
    case DO_NOT_DISTURB_PERIOD_REQUIRED = 520045;

    public function template(): string
    {
        return match ($this) {
            self::METHOD_NOT_FOUND => '消息方法 %s 不存在',
            self::TEMPLATE_NOT_FOUND => '消息模板不存在：%s',
            self::MESSAGE_NOT_VISIBLE => '通知消息尚不可见或已删除',
            self::RECEIPT_NOT_VISIBLE => '通知收件记录尚不可见',
            self::MESSAGE_NOT_FOUND => '消息不存在：%s',
            self::MESSAGE_NOT_SENDABLE => '当前消息不可发送：%s',
            self::RECIPIENTS_NOT_FOUND => '消息未找到收件人：%s',
            self::FIELD_REQUIRED => '字段 %s 不能为空',
            self::TITLE_TOO_LONG => '消息标题不能超过 %s 个字',
            self::CONTENT_TOO_LONG => '消息内容不能超过 %s 个字',
            self::MESSAGE_TYPE_INVALID => '消息类型无效：%s',
            self::RECIPIENT_TYPE_INVALID => '收件人类型无效：%s',
            self::PRIORITY_INVALID => '消息优先级必须在 1 至 5 之间',
            self::QUEUE_REJECTED => '通知队列拒绝接收任务: %s',
            self::CHANNEL_UNCONFIRMED => '通知渠道未确认发送成功: %s',
            self::CHANNEL_UNSUPPORTED => '不支持的通知渠道：%s',
            self::MINIAPP_SINGLE_RECIPIENT_REQUIRED => '小程序通知必须明确指定单个收件人，不能复用 openid 群发',
            self::MINIAPP_RECEIPT_FAILED => '微信订阅消息未确认成功，错误码: %s',
            self::TEMPLATE_IN_USE => '消息正在使用该模板，无法删除',
            self::TEMPLATE_NAME_TOO_LONG => '模板名称不能超过 %s 个字',
            self::TEMPLATE_NAME_DUPLICATE => '消息模板名称已存在：%s',
            self::TEMPLATE_TYPE_INVALID => '消息模板类型无效：%s',
            self::TEMPLATE_VARIABLE_INVALID => '字段 %s 的模板变量名无效：%s',
            self::TEMPLATE_SYNTAX_INVALID => '字段 %s 的模板语法无效：%s',
            self::EMAIL_ADDRESS_INVALID => '邮件收件人或发件人配置无效',
            self::EMAIL_SUBJECT_INVALID => '邮件标题不能包含换行',
            self::EMAIL_TRANSPORT_REJECTED => '邮件传输服务拒绝发送',
            self::GATEWAY_CHANNEL_UNKNOWN => '未知通知网关渠道',
            self::GATEWAY_CONFIGURATION_INVALID => '通知网关未配置有效 HTTPS 地址和认证令牌: %s',
            self::GATEWAY_RECEIPT_FAILED => '通知网关未确认接收: %s HTTP %s',
            self::MESSAGE_RECORD_NOT_FOUND => '消息不存在',
            self::MESSAGE_DELETE_SELECTION_REQUIRED => '请选择要删除的消息',
            self::MESSAGE_ID_REQUIRED => '消息ID不能为空',
            self::MESSAGE_SCHEDULE_INPUT_REQUIRED => '消息ID和调度时间不能为空',
            self::MESSAGE_SEND_SELECTION_REQUIRED => '请选择要发送的消息',
            self::SEARCH_KEYWORD_REQUIRED => '搜索关键词不能为空',
            self::TEMPLATE_RECORD_NOT_FOUND => '模板不存在',
            self::TEMPLATE_DELETE_SELECTION_REQUIRED => '请选择要删除的模板',
            self::TEMPLATE_ID_REQUIRED => '模板ID不能为空',
            self::TEMPLATE_DATA_REQUIRED => '模板数据不能为空',
            self::MESSAGE_NOT_FOUND_OR_READ => '消息不存在或已读',
            self::MESSAGE_MARK_SELECTION_REQUIRED => '请选择要标记的消息',
            self::CHANNEL_SETTINGS_REQUIRED => '渠道设置不能为空',
            self::TYPE_SETTINGS_REQUIRED => '消息类型设置不能为空',
            self::DO_NOT_DISTURB_PERIOD_REQUIRED => '开始时间和结束时间不能为空',
        };
    }
}
