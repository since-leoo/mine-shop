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

namespace Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong;

use App\Infrastructure\Error\SupplyChainError;

/** Matches the active legacy client, while avoiding PHP 8's nonnumeric-string addition error. */
final class Signer
{
    public function sign(array $parameters, #[\SensitiveParameter] string $secret, string $body = ''): string
    {
        ksort($parameters);
        $canonical = '';
        foreach ($parameters as $key => $value) {
            if (! \is_scalar($value) && $value !== null) {
                throw SupplyChainError::SIGNATURE_PARAMETER_INVALID->invalid();
            }
            $canonical .= $key . (string) $value;
        }
        return mb_strtoupper(md5(sha1($canonical . $secret . $body)));
    }

    public function nonce(string $appKey, string $timestamp): string
    {
        // Old PHP numeric addition: "application4" + timestamp => timestamp. Do not silently change to concatenation.
        preg_match('/^[\s]*([+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?)/', $appKey, $matches);
        $numericKey = isset($matches[1]) ? (float) $matches[1] : 0;
        return md5((string) ($numericKey + (int) $timestamp));
    }
}
