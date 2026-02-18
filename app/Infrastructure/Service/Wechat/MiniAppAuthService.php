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

    /**
     * 获取小程序码.
     *
     * @return array{path?: string, name?: string, msg: string}
     */
    public function getWxaCode(string $page, string $scene, array $options = []): array
    {
        // 确保二维码保存目录存在
        $savePath = $options['save_path'] ?? '/uploadfile/wechat/mini/qrcode/';
        $dir = BASE_PATH . '/public' . $savePath;
        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        try {
            $result = $this->miniApp->getLimitedWxaCode($page, $scene, false, $options);
        } catch (\Throwable $e) {
            return ['path' => '', 'msg' => '获取小程序码失败: ' . $e->getMessage()];
        }

        // 修正返回的 path 为可访问的相对路径
        if (! empty($result['name']) && file_exists($result['name'])) {
            $result['path'] = $savePath . basename($result['name']);
        }

        return $result;
    }
}
