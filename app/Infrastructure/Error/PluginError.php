<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum PluginError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case CONFIG_NOT_OWNED = 160001;
    case NOT_FOUND = 160002;
    case FINANCIAL_UNINSTALL_FORBIDDEN = 160003;
    case DATA_UNINSTALL_FORBIDDEN = 160004;
    case UNSETTLED_FUNDS = 160005;
    case PENDING_ORDERS = 160006;
    case CACHE_DIRECTORY_FAILED = 160007;
    case CACHE_LOCK_OPEN_FAILED = 160008;
    case CACHE_LOCK_FAILED = 160009;
    case FINGERPRINT_REVOKE_FAILED = 160010;
    case STATE_CHANGED_DURING_SCAN = 160011;
    case FINGERPRINT_WRITE_FAILED = 160012;
    case DIRECTORY_UNREADABLE = 160013;
    case MANIFEST_UNREADABLE = 160014;
    case INSTALL_MARKER_UNREADABLE = 160015;
    case CACHE_REFRESH_FAILED = 160016;
    case PERMISSION_CODE_CONFLICT = 160017;
    case PERMISSION_GROUP_INVALID = 160018;
    case PERMISSION_CODE_INVALID = 160019;
    case PERMISSION_NAME_INVALID = 160020;

    case CAPABILITY_BINDING_INVALID = 160021;

    case CAPABILITY_IMPLEMENTATION_INVALID = 160022;

    case UNINSTALL_DECLARATION_INVALID = 160023;

    public function template(): string
    {
        return match ($this) {
            self::UNINSTALL_DECLARATION_INVALID => '插件卸载保护声明包含无效表或字段标识',
            self::CAPABILITY_IMPLEMENTATION_INVALID => '能力实现未实现接口：%s',
            self::CAPABILITY_BINDING_INVALID => '能力绑定无效：%s',
            self::CONFIG_NOT_OWNED => '配置项不属于该插件',
            self::NOT_FOUND => '插件不存在',
            self::FINANCIAL_UNINSTALL_FORBIDDEN => '该插件曾启用财务业务，不支持物理卸载；请关闭新增业务并保留历史处理',
            self::DATA_UNINSTALL_FORBIDDEN => '插件已有业务记录，不支持物理卸载；请在插件设置中关闭新增业务',
            self::UNSETTLED_FUNDS => '插件存在未结清资金，无法卸载',
            self::PENDING_ORDERS => '插件仍有待处理订单，无法卸载',
            self::CACHE_DIRECTORY_FAILED => '无法创建插件扫描缓存目录',
            self::CACHE_LOCK_OPEN_FAILED => '无法打开插件扫描缓存锁',
            self::CACHE_LOCK_FAILED => '无法锁定插件扫描缓存',
            self::FINGERPRINT_REVOKE_FAILED => '无法撤销插件扫描指纹',
            self::STATE_CHANGED_DURING_SCAN => '插件状态在启动扫描期间发生变化，请重新启动',
            self::FINGERPRINT_WRITE_FAILED => '无法保存插件扫描指纹',
            self::DIRECTORY_UNREADABLE => '无法读取插件目录',
            self::MANIFEST_UNREADABLE => '无法读取插件清单: %s',
            self::INSTALL_MARKER_UNREADABLE => '无法读取插件安装标记: %s',
            self::CACHE_REFRESH_FAILED => '无法刷新插件扫描缓存: %s',
            self::PERMISSION_CODE_CONFLICT => '插件权限码已被其他权限组占用',
            self::PERMISSION_GROUP_INVALID => '插件权限组标识无效',
            self::PERMISSION_CODE_INVALID => '插件权限码必须使用本插件命名空间且不超过50字节',
            self::PERMISSION_NAME_INVALID => '插件权限名称不能为空或超过100字符',
        };
    }
}
