<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum PermissionError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case OWNER_OUT_OF_SCOPE = 140001;
    case CUSTOM_FUNCTION_INVALID = 140002;
    case CUSTOM_FUNCTION_RETURN_INVALID = 140003;
    case NAMED_FUNCTION_NOT_FOUND = 140004;
    case CUSTOM_FUNCTION_NOT_FOUND = 140005;
    case BUTTON_CODE_REQUIRED = 140006;
    case BUTTON_NAME_REQUIRED = 140007;
    case MENU_PARENT_INVALID = 140008;
    case MENU_NAME_REQUIRED = 140009;
    case USER_ROLE_GRANT_DISABLED = 140010;
    case USER_PASSWORD_RESET_DISABLED = 140011;
    case ROLE_NAME_REQUIRED = 140012;
    case ROLE_CODE_REQUIRED = 140013;
    case ROLE_GRANT_DISABLED = 140014;
    case SUPER_ADMIN_PERMISSION_IMMUTABLE = 140015;
    case ROLE_NOT_FOUND = 140016;

    public function template(): string
    {
        return match ($this) {
            self::OWNER_OUT_OF_SCOPE => '所属记录不存在或不在当前数据权限范围内',
            self::CUSTOM_FUNCTION_INVALID => '自定义权限函数无效：%s',
            self::CUSTOM_FUNCTION_RETURN_INVALID => '自定义权限函数必须返回集合',
            self::NAMED_FUNCTION_NOT_FOUND => '自定义权限函数 %s 不存在',
            self::CUSTOM_FUNCTION_NOT_FOUND => '自定义权限函数不存在',
            self::BUTTON_CODE_REQUIRED => '按钮权限编码不能为空',
            self::BUTTON_NAME_REQUIRED => '按钮权限名称不能为空',
            self::MENU_PARENT_INVALID => '父级菜单无效',
            self::MENU_NAME_REQUIRED => '菜单名称不能为空',
            self::USER_ROLE_GRANT_DISABLED => '用户状态为 %s，无法授予角色',
            self::USER_PASSWORD_RESET_DISABLED => '用户状态为 %s，无法重置密码',
            self::ROLE_NAME_REQUIRED => '角色名称不能为空',
            self::ROLE_CODE_REQUIRED => '角色编码不能为空',
            self::ROLE_GRANT_DISABLED => '角色状态为 %s，无法授予权限',
            self::SUPER_ADMIN_PERMISSION_IMMUTABLE => '超级管理员角色的权限不能被修改',
            self::ROLE_NOT_FOUND => '角色不存在: ID=%s',
        };
    }
}
