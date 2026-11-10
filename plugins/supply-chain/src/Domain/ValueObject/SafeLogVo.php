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

namespace Plugin\SupplyChain\Domain\ValueObject;

/** Defense in depth for task parameters as well as HTTP logs. */
final class SafeLogVo
{
    public static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 12) {
            return '[depth-limit]';
        }
        if (\is_array($value)) {
            $safe = [];
            foreach ($value as $key => $item) {
                if (preg_match('/secret|token|authorization|signature|member.?sign|credential|api.?sign|password|ciphertext|app.?key/i', (string) $key)) {
                    $safe[$key] = '[redacted]';
                } elseif (preg_match('/phone|mobile|address|consignee|receiver|street|description/i', (string) $key)) {
                    $safe[$key] = '[masked]';
                } else {
                    $safe[$key] = self::sanitize($item, $depth + 1);
                }
            }
            return $safe;
        }
        if (\is_object($value)) {
            return '[object]';
        }
        if (\is_string($value)) {
            $value = preg_replace('/(secret|token|authorization|member[_-]?sign|app[_-]?key)\s*[=:]\s*[^\s,;]+/i', '$1=[redacted]', $value) ?? '';
            return mb_substr(preg_replace('/(?<!\d)1[3-9]\d{9}(?!\d)/', '[phone]', $value) ?? '', 0, 8000);
        }
        return $value;
    }

    public static function error(\Throwable $error): string
    {
        // SDK messages are already sanitized. Remove credentials embedded by downstream exceptions too.
        $message = preg_replace('/(secret|token|authorization|app[_-]?key)\s*[=:]\s*[^\s,;]+/i', '$1=[redacted]', $error->getMessage()) ?? '';
        return mb_substr((string) self::sanitize($message), 0, 1000);
    }
}
