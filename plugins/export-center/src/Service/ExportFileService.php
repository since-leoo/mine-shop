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

use App\Infrastructure\Error\ExportError;

final class ExportFileService
{
    public function store(string $source, string $fingerprint): string
    {
        $directory = $this->directory();
        if (! is_dir($directory) && ! mkdir($directory, 0o700, true) && ! is_dir($directory)) {
            throw ExportError::PRIVATE_DIRECTORY_CREATE_FAILED->internal();
        }
        $key = 'private:' . $fingerprint . ':' . bin2hex(random_bytes(16)) . '.' . pathinfo($source, \PATHINFO_EXTENSION);
        $path = $this->path($key);
        if (! rename($source, $path)) {
            throw ExportError::PRIVATE_FILE_SAVE_FAILED->internal();
        }
        if (! chmod($path, 0o600)) {
            unlink($path);
            throw ExportError::FILE_PERMISSIONS_FAILED->internal();
        }
        return $key;
    }

    public function path(string $key, ?string $fingerprint = null): string
    {
        if (! preg_match('/^private:([a-f0-9]{64}):([a-f0-9]{32}\.(?:xlsx|csv|zip))$/D', $key, $match)) {
            throw ExportError::LEGACY_FILE_DISABLED->exception();
        }
        if ($fingerprint !== null && ! hash_equals($match[1], $fingerprint)) {
            throw ExportError::DATA_SCOPE_CHANGED->exception();
        }
        $path = $this->directory() . '/' . $match[2];
        if (is_link($path)) {
            throw ExportError::FILE_UNAVAILABLE->exception();
        }
        return $path;
    }

    public function deleteFile(string $path): bool
    {
        if (! str_starts_with($path, 'private:')) {
            return false;
        }
        $path = $this->path($path);
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    public function fileExists(string $path): bool
    {
        return str_starts_with($path, 'private:') && is_file($this->path($path));
    }

    public function getFileSize(string $path): int
    {
        $path = $this->path($path);
        if (! is_file($path)) {
            return 0;
        }

        $size = filesize($path);
        return $size === false ? 0 : $size;
    }

    private function directory(): string
    {
        $path = (string) config('export.private_path', BASE_PATH . '/storage/exports/private');
        // Resolve existing ancestors as well, so a symlink or ../ cannot place exports under public/.
        $ancestor = $path;
        while (! file_exists($ancestor) && \dirname($ancestor) !== $ancestor) {
            $ancestor = \dirname($ancestor);
        }
        $resolved = realpath($ancestor);
        $public = realpath(BASE_PATH . '/public');
        if ($resolved === false || ($public !== false && ($resolved === $public || str_starts_with($resolved, $public . '/')))) {
            throw ExportError::PRIVATE_DIRECTORY_REQUIRED->internal();
        }
        return rtrim($path, '/');
    }
}
