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

namespace App\Infrastructure\Service\Wechat;

use Plugin\Wechat\Interfaces\MiniAppInterface;

final class MiniAppAuthService
{
    public function __construct(private readonly MiniAppInterface $miniApp) {}

    /**
     * @return array<string, mixed>
     */
    public function silentAuthorize(string $code): array
    {
        return $this->miniApp->silentAuthorize($code);
    }

    /**
     * @return array<string, mixed>
     */
    public function decryptUserInfo(string $code, string $encryptedData, string $iv): array
    {
        return $this->miniApp->performSilentLogin($code, $encryptedData, $iv);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPhoneNumber(string $code): array
    {
        return $this->miniApp->getPhoneNumber($code);
    }
}
