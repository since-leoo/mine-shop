<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum DistributionError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case COMMISSION_RECOVERY_PENDING = 410001;
    case REFUND_RECOVERY_PENDING = 410002;
    case COMMISSION_BALANCE_INCONSISTENT = 410003;
    case AMOUNT_NEGATIVE = 410004;
    case WITHDRAWAL_BELOW_MINIMUM = 410005;
    case WITHDRAWAL_STATE_CHANGED = 410006;
    case PAYMENT_REFERENCE_REQUIRED = 410007;
    case WITHDRAWAL_REJECTION_REASON_REQUIRED = 410008;
    case TEAM_ACCESS_DENIED = 410009;
    case MEMBER_DATA_ACCESS_DENIED = 410010;
    case TEAM_SELF_EXCLUDED = 410011;
    case FIRST_ORDER_PAYMENT_MISSING = 410012;
    case ORDER_ATTRIBUTION_MISMATCH = 410013;
    case COMMISSION_SNAPSHOT_MISMATCH = 410014;
    case ITEM_ATTRIBUTION_ORDER_MISMATCH = 410015;
    case MEMBER_NOT_FOUND = 410016;
    case MEMBER_SHARE_FORBIDDEN = 410017;
    case SHARE_PRODUCT_REQUIRED = 410018;
    case MEMBER_WITHDRAWAL_FORBIDDEN = 410019;
    case WITHDRAWAL_IDEMPOTENCY_CONFLICT = 410020;
    case WITHDRAWAL_IN_PROGRESS = 410021;
    case WITHDRAWAL_NOT_FOUND = 410022;
    case POLICY_PARAMETERS_INVALID = 410023;
    case COMMISSION_MODE_UNSUPPORTED = 410024;
    case SKU_COMMISSION_DUPLICATED = 410025;
    case PRODUCT_COMMISSION_INVALID = 410026;
    case H5_SITE_URL_INVALID = 410027;
    case ITEM_AMOUNT_ALLOCATION_INVALID = 410028;
    case INVITATION_QR_CODE_FORBIDDEN = 410029;
    case FINANCIAL_PLUGIN_UNINSTALL_FORBIDDEN = 410030;
    case PLUGIN_HISTORY_UNINSTALL_FORBIDDEN = 410031;
    case UNSETTLED_FUNDS_UNINSTALL_FORBIDDEN = 410032;
    case PENDING_ATTRIBUTION_UNINSTALL_FORBIDDEN = 410033;
    case WECHAT_PLUGIN_NOT_CONFIGURED = 410034;
    case SHARE_TOKEN_INVALID = 410035;
    case WECHAT_SHARE_FAILED = 410036;
    case WECHAT_SHARE_RESULT_MISSING = 410037;
    case SUPER_ADMIN_REQUIRED = 410038;
    case ADMIN_DISABLED = 410039;
    case WECHAT_ACCOUNT_PERMISSION_REQUIRED = 410040;

    public function template(): string
    {
        return match ($this) {
            self::COMMISSION_RECOVERY_PENDING => '存在待追回佣金，暂不能提现',
            self::REFUND_RECOVERY_PENDING => '退款形成待追回金额，须先处理后再确认打款',
            self::COMMISSION_BALANCE_INCONSISTENT => '佣金余额不足或账目不一致',
            self::AMOUNT_NEGATIVE => '金额不能为负',
            self::WITHDRAWAL_BELOW_MINIMUM => '未达到最低提现金额',
            self::WITHDRAWAL_STATE_CHANGED => '提现状态已变化，请刷新后操作',
            self::PAYMENT_REFERENCE_REQUIRED => '确认打款须填写唯一付款流水号',
            self::WITHDRAWAL_REJECTION_REASON_REQUIRED => '驳回或失败须填写原因',
            self::TEAM_ACCESS_DENIED => '无权查看该团队',
            self::MEMBER_DATA_ACCESS_DENIED => '无权查看该成员的数据',
            self::TEAM_SELF_EXCLUDED => '团队数据不包含本人',
            self::FIRST_ORDER_PAYMENT_MISSING => '首单归因缺少已支付订单',
            self::ORDER_ATTRIBUTION_MISMATCH => '分销订单归因不匹配',
            self::COMMISSION_SNAPSHOT_MISMATCH => '订单商品与佣金快照不一致',
            self::ITEM_ATTRIBUTION_ORDER_MISMATCH => '商品归因快照顺序不一致',
            self::MEMBER_NOT_FOUND => '会员不存在',
            self::MEMBER_SHARE_FORBIDDEN => '当前会员不能生成推广分享',
            self::SHARE_PRODUCT_REQUIRED => '商品分享缺少商品ID',
            self::MEMBER_WITHDRAWAL_FORBIDDEN => '当前会员不可申请提现',
            self::WITHDRAWAL_IDEMPOTENCY_CONFLICT => '重复申请标识对应不同提现内容',
            self::WITHDRAWAL_IN_PROGRESS => '已有进行中的提现申请，请先等待处理',
            self::WITHDRAWAL_NOT_FOUND => '提现申请不存在',
            self::POLICY_PARAMETERS_INVALID => '佣金、提现或结算参数不合法',
            self::COMMISSION_MODE_UNSUPPORTED => '不支持的计佣模式',
            self::SKU_COMMISSION_DUPLICATED => '同一商品规格不能重复配置佣金',
            self::PRODUCT_COMMISSION_INVALID => '商品佣金规则不合法',
            self::H5_SITE_URL_INVALID => 'H5地址应为不带参数和片段的完整站点地址',
            self::ITEM_AMOUNT_ALLOCATION_INVALID => '商品金额分摊异常',
            self::INVITATION_QR_CODE_FORBIDDEN => '当前不可生成邀请二维码',
            self::FINANCIAL_PLUGIN_UNINSTALL_FORBIDDEN => '该插件曾启用财务业务，不支持物理卸载；请在插件设置中关闭新增业务，保留历史结算与退款处理',
            self::PLUGIN_HISTORY_UNINSTALL_FORBIDDEN => '插件已有业务记录，不支持物理卸载；请在插件设置中关闭新增业务',
            self::UNSETTLED_FUNDS_UNINSTALL_FORBIDDEN => '插件存在未结清资金，无法卸载；请先关闭新增业务并完成结算',
            self::PENDING_ATTRIBUTION_UNINSTALL_FORBIDDEN => '插件仍有待处理的归因订单，无法卸载',
            self::WECHAT_PLUGIN_NOT_CONFIGURED => '微信插件未配置，请使用普通邀请链接',
            self::SHARE_TOKEN_INVALID => '小程序码缺少有效分享标识',
            self::WECHAT_SHARE_FAILED => '微信分享生成失败：%s',
            self::WECHAT_SHARE_RESULT_MISSING => '未获取到微信分享结果，请检查微信配置或重试',
            self::SUPER_ADMIN_REQUIRED => '分销管理仅限超级管理员',
            self::ADMIN_DISABLED => '当前管理员已停用',
            self::WECHAT_ACCOUNT_PERMISSION_REQUIRED => '请检查微信账号权限',
        };
    }
}
