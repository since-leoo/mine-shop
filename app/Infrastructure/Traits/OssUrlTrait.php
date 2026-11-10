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

namespace App\Infrastructure\Traits;

use Qiniu\Auth;
use function Hyperf\Config\config;

trait OssUrlTrait
{
    protected function getSignFile(string $url): string
    {
        $fileStorage = config('file.default');
        $options = config('file.storage.' . $fileStorage);

        $auth = new Auth(
            $options['accessKey'],
            $options['secretKey'],
        );
    }
}
