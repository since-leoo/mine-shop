<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum DiyError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case DEFAULT_PAGE_VALIDATION_FAILED = 510001;
    case DEFAULT_PAGE_DEFINITION_MISSING = 510002;
    case PAGE_KEY_REQUIRED = 510003;
    case PAGE_NAME_REQUIRED = 510004;
    case PAGE_TYPE_INVALID = 510005;
    case PUBLISHED_PAGE_REQUIRED_FOR_ENABLE = 510006;
    case DRAFT_REQUIRED_FOR_PUBLISH = 510007;
    case PAGE_OUTSIDE_DATA_SCOPE = 510008;
    case PUBLISH_CURRENT_DRAFT_REQUIRED = 510009;
    case PUBLISH_VALIDATION_FAILED = 510010;
    case PAGE_NOT_FOUND = 510011;
    case SCHEDULE_TIME_INVALID = 510012;
    case SCHEDULE_OPERATOR_REQUIRED = 510013;
    case PAGE_VERSION_NOT_FOUND = 510014;
    case SCHEDULE_ALREADY_PENDING = 510015;
    case PUBLISH_RECORD_NOT_FOUND = 510016;
    case PENDING_SCHEDULE_REQUIRED_FOR_CANCEL = 510017;
    case SCHEDULE_OPERATOR_UNAUTHORIZED = 510018;
    case SCHEDULE_VERSION_NOT_FOUND = 510019;
    case ROLLBACK_PUBLISHED_VERSION_REQUIRED = 510020;
    case PREVIEW_TOKEN_NOT_FOUND = 510021;
    case PREVIEW_TOKEN_EXPIRED = 510022;
    case TEMPLATE_DISABLED = 510023;
    case PUBLISHED_PAGE_REQUIRED_FOR_TEMPLATE = 510024;
    case TEMPLATE_NOT_FOUND = 510025;
    case COMPONENT_UNSUPPORTED = 510026;
    case COUPON_LIMIT_EXCEEDED = 510027;
    case DISTRIBUTION_RUNTIME_DATA_FORBIDDEN = 510028;
    case DISTRIBUTION_TITLE_TOO_LONG = 510029;
    case PROFILE_RUNTIME_DATA_FORBIDDEN = 510030;
    case DISPLAY_SWITCH_INVALID = 510031;
    case IMAGE_AD_LAYOUT_INVALID = 510032;
    case PRODUCT_RANK_TYPE_INVALID = 510033;
    case PRODUCT_SOURCE_INVALID = 510034;
    case PRODUCT_SORT_INVALID = 510035;
    case SEARCH_PLACEHOLDER_TOO_LONG = 510036;
    case IMAGE_CUBE_LAYOUT_INVALID = 510037;
    case IMAGE_WIDTH_MODE_INVALID = 510038;
    case IMAGE_WIDTH_UNIT_INVALID = 510039;
    case IMAGE_PERCENT_WIDTH_INVALID = 510040;
    case IMAGE_WIDTH_INVALID = 510041;
    case IMAGE_HEIGHT_INVALID = 510042;
    case IMAGE_RADIUS_INVALID = 510043;
    case IMAGE_FIT_INVALID = 510044;
    case LINK_TYPE_UNSUPPORTED = 510045;
    case SCHEMA_VERSION_INVALID = 510046;
    case PAGE_INFORMATION_REQUIRED = 510047;
    case PAGE_KEY_MISMATCH = 510048;
    case COMPONENTS_REQUIRED = 510049;
    case THEME_FORMAT_INVALID = 510050;
    case THEME_COLOR_INVALID = 510051;
    case CARD_RADIUS_INVALID = 510052;
    case BUTTON_STYLE_INVALID = 510053;
    case COMPONENT_LIMIT_EXCEEDED = 510054;
    case COMPONENT_FORMAT_INVALID = 510055;
    case COMPONENT_ID_REQUIRED = 510056;
    case COMPONENT_ID_DUPLICATE = 510057;

    case QUICK_NAV_LIMIT_EXCEEDED = 510058;
    case CATEGORY_PANEL_LIMIT_EXCEEDED = 510059;
    case NOTICE_LIMIT_EXCEEDED = 510060;
    case MARKETING_ENTRY_LIMIT_EXCEEDED = 510061;
    case SECKILL_ACTIVITY_REQUIRED = 510062;
    case GROUP_BUY_ACTIVITY_REQUIRED = 510063;
    case USER_STATS_LIMIT_EXCEEDED = 510064;
    case ORDER_ENTRY_LIMIT_EXCEEDED = 510065;
    case USER_MENU_LIMIT_EXCEEDED = 510066;
    case BANNER_LIMIT_EXCEEDED = 510067;
    case IMAGE_AD_LIMIT_EXCEEDED = 510068;
    case MARKETING_PRODUCT_LIMIT_EXCEEDED = 510069;
    case PRODUCT_RANK_LIMIT_EXCEEDED = 510070;
    case PRODUCT_GROUP_LIMIT_EXCEEDED = 510071;
    case IMAGE_CUBE_LIMIT_EXCEEDED = 510072;

    case SCHEDULE_EXECUTION_FAILED = 510073;

    public function template(): string
    {
        return match ($this) {
            self::DEFAULT_PAGE_VALIDATION_FAILED => '默认DIY页面发布校验失败：%s/%s - %s',
            self::DEFAULT_PAGE_DEFINITION_MISSING => '默认装修页面定义缺失',
            self::PAGE_KEY_REQUIRED => '页面键不能为空',
            self::PAGE_NAME_REQUIRED => '页面名称不能为空',
            self::PAGE_TYPE_INVALID => '页面类型无效',
            self::PUBLISHED_PAGE_REQUIRED_FOR_ENABLE => '请先发布页面后再启用',
            self::DRAFT_REQUIRED_FOR_PUBLISH => '请先保存草稿后再发布',
            self::PAGE_OUTSIDE_DATA_SCOPE => 'DIY页面不存在或不在当前数据权限范围内',
            self::PUBLISH_CURRENT_DRAFT_REQUIRED => '只能发布当前页面的草稿版本',
            self::PUBLISH_VALIDATION_FAILED => '发布前校验失败：%s - %s',
            self::PAGE_NOT_FOUND => 'DIY页面不存在',
            self::SCHEDULE_TIME_INVALID => '定时发布时间必须晚于当前时间',
            self::SCHEDULE_OPERATOR_REQUIRED => '定时发布需要有效的后台操作人',
            self::PAGE_VERSION_NOT_FOUND => '页面版本不存在',
            self::SCHEDULE_ALREADY_PENDING => '已有待执行的定时发布',
            self::PUBLISH_RECORD_NOT_FOUND => '发布记录不存在',
            self::PENDING_SCHEDULE_REQUIRED_FOR_CANCEL => '只能取消待发布记录',
            self::SCHEDULE_OPERATOR_UNAUTHORIZED => '定时发布操作人已失效或没有发布权限',
            self::SCHEDULE_VERSION_NOT_FOUND => '定时发布版本不存在',
            self::ROLLBACK_PUBLISHED_VERSION_REQUIRED => '只能回滚到历史已发布版本',
            self::PREVIEW_TOKEN_NOT_FOUND => '预览令牌不存在',
            self::PREVIEW_TOKEN_EXPIRED => '预览令牌已过期',
            self::TEMPLATE_DISABLED => '模板已禁用',
            self::PUBLISHED_PAGE_REQUIRED_FOR_TEMPLATE => '请先发布页面后再保存为模板',
            self::TEMPLATE_NOT_FOUND => '装修模板不存在',
            self::COMPONENT_UNSUPPORTED => '不支持的装修组件',
            self::COUPON_LIMIT_EXCEEDED => '优惠券组最多10张',
            self::DISTRIBUTION_RUNTIME_DATA_FORBIDDEN => '分销组件仅允许展示配置，会员与佣金数据由服务端实时提供',
            self::DISTRIBUTION_TITLE_TOO_LONG => '分销模块标题不能超过50个字',
            self::PROFILE_RUNTIME_DATA_FORBIDDEN => '用户头部不能配置运行时会员数据或分销资格',
            self::DISPLAY_SWITCH_INVALID => '展示开关必须为布尔值',
            self::IMAGE_AD_LAYOUT_INVALID => '图片广告布局无效',
            self::PRODUCT_RANK_TYPE_INVALID => '商品榜单类型无效',
            self::PRODUCT_SOURCE_INVALID => '商品组来源无效',
            self::PRODUCT_SORT_INVALID => '商品组排序无效',
            self::SEARCH_PLACEHOLDER_TOO_LONG => '搜索框占位文案最多30个字',
            self::IMAGE_CUBE_LAYOUT_INVALID => '图片魔方布局无效',
            self::IMAGE_WIDTH_MODE_INVALID => '图片组件宽度模式无效',
            self::IMAGE_WIDTH_UNIT_INVALID => '图片组件宽度单位无效',
            self::IMAGE_PERCENT_WIDTH_INVALID => '图片组件百分比宽度需在1-100之间',
            self::IMAGE_WIDTH_INVALID => '图片组件宽度需在1-750之间',
            self::IMAGE_HEIGHT_INVALID => '图片组件高度需在0-1000之间',
            self::IMAGE_RADIUS_INVALID => '图片组件圆角需在0-100之间',
            self::IMAGE_FIT_INVALID => '图片组件填充方式无效',
            self::LINK_TYPE_UNSUPPORTED => '不支持的跳转类型',
            self::SCHEMA_VERSION_INVALID => '装修页面结构版本无效',
            self::PAGE_INFORMATION_REQUIRED => '装修页面信息不能为空',
            self::PAGE_KEY_MISMATCH => '装修页面键不一致',
            self::COMPONENTS_REQUIRED => '装修组件不能为空',
            self::THEME_FORMAT_INVALID => '装修主题格式无效',
            self::THEME_COLOR_INVALID => '装修主题颜色无效',
            self::CARD_RADIUS_INVALID => '装修卡片圆角需在0-64之间',
            self::BUTTON_STYLE_INVALID => '装修按钮样式无效',
            self::COMPONENT_LIMIT_EXCEEDED => '装修组件最多50个',
            self::COMPONENT_FORMAT_INVALID => '装修组件格式无效',
            self::COMPONENT_ID_REQUIRED => '组件ID不能为空',
            self::COMPONENT_ID_DUPLICATE => '组件ID重复',
            self::QUICK_NAV_LIMIT_EXCEEDED => '金刚区最多20个入口',
            self::CATEGORY_PANEL_LIMIT_EXCEEDED => '商品分类最多20个一级分类',
            self::NOTICE_LIMIT_EXCEEDED => '公告栏最多10条',
            self::MARKETING_ENTRY_LIMIT_EXCEEDED => '营销入口最多10个',
            self::SECKILL_ACTIVITY_REQUIRED => '秒杀组件必须选择活动',
            self::GROUP_BUY_ACTIVITY_REQUIRED => '拼团组件必须选择活动',
            self::USER_STATS_LIMIT_EXCEEDED => '用户统计最多8项',
            self::ORDER_ENTRY_LIMIT_EXCEEDED => '订单入口最多10项',
            self::USER_MENU_LIMIT_EXCEEDED => '用户菜单最多10项',
            self::BANNER_LIMIT_EXCEEDED => '轮播图最多10张',
            self::IMAGE_AD_LIMIT_EXCEEDED => '图片广告最多10张',
            self::MARKETING_PRODUCT_LIMIT_EXCEEDED => '营销组件商品最多20个',
            self::PRODUCT_RANK_LIMIT_EXCEEDED => '商品榜单数量最多50个',
            self::PRODUCT_GROUP_LIMIT_EXCEEDED => '商品组数量最多50个',
            self::IMAGE_CUBE_LIMIT_EXCEEDED => '图片魔方最多10张',
            self::SCHEDULE_EXECUTION_FAILED => '定时发布失败，请查看服务日志后重新预约',
        };
    }
}
