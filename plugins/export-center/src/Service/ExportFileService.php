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

namespace Plugin\ExportCenter\Service;

final class ExportFileService
{
    public function deleteFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    public function fileExists(string $path): bool
    {
        return is_file($path);
    }

    public function getFileSize(string $path): int
    {
        if (! is_file($path)) {
            return 0;
        }

        $size = filesize($path);
        return $size === false ? 0 : $size;
    }
}
