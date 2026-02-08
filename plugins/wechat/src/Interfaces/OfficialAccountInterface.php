<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\Wechat\Interfaces;

use EasyWeChat\Exceptions\InvalidArgumentException;

interface OfficialAccountInterface
{
    /**
     * 创建授权链接.
     *
     * 该方法用于生成用户授权的URL链接，用户需点击该链接进行授权操作
     * 主要用于OAuth认证流程中，引导用户进行授权确认
     *
     * @param string $redirectUrl 授权后回调的URL地址，需提前在平台配置
     * @return string 返回生成的授权URL链接
     *
     * @throws InvalidArgumentException 如果提供的回调URL未在平台配置或格式不正确，抛出此异常
     */
    public function createAuthorizationUrl(string $redirectUrl = ''): string;

    /**
     * 授权回调获取用户信息.
     *
     * 用户在授权页面完成授权后，通过此方法获取用户的详细信息
     * 主要用于OAuth认证流程中，获取用户的基本资料等信息
     *
     * @param string $code 授权成功后返回的授权码，用于换取用户信息
     * @return array 返回包含用户信息的数组，如用户名、邮箱等
     *
     * @throws InvalidArgumentException 如果授权码不正确或已过期，抛出此异常
     */
    public function getUserInfo(string $code): array;
}
