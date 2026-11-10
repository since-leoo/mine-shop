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

namespace Plugin\SupplyChain\Infrastructure\Security;

final class LogSanitizer
{
    private const SECRET_KEYS = ['appsecret', 'appkey', 'secret', 'token', 'xtoken', 'accesstoken',
        'refreshtoken', 'authorization', 'apisign', 'signature', 'membersign', 'password', 'credential', 'ciphertext', 'apiappkey', 'appsecretciphertext'];

    private const PRIVATE_KEYS = ['phone', 'mobile', 'telephone', 'consignee', 'description', 'address',
        'shippingaddress', 'receiver', 'contact', 'name', 'remark'];

    /** Capture request PII so a provider echoing it into a free-form error also gets redacted. */
    public function sensitiveValues(array $parameters): array
    {
        $values = [];
        foreach ($parameters as $key => $value) {
            if (\is_array($value)) {
                $values = array_merge($values, $this->sensitiveValues($value));
            } elseif (\is_string($value) && $value !== ''
                && \in_array(mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $key)), self::PRIVATE_KEYS, true)) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /** Secrets may also appear embedded in vendor messages or transport diagnostics. */
    public function sanitize(mixed $value, array $secrets = []): mixed
    {
        if (\is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $normalized = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $key));
                if (\in_array($normalized, self::SECRET_KEYS, true)) {
                    $out[$key] = '[REDACTED]';
                } elseif (\in_array($normalized, self::PRIVATE_KEYS, true)) {
                    $out[$key] = \is_array($item) ? $this->sanitize($item, $secrets) : '[MASKED]';
                } else {
                    $out[$key] = $this->sanitize($item, $secrets);
                }
            }
            return $out;
        }
        if (! \is_string($value)) {
            return $value;
        }
        foreach ($secrets as $secret) {
            if (\is_string($secret) && $secret !== '') {
                $value = str_replace([$secret, rawurlencode($secret), urlencode($secret)], '[REDACTED]', $value);
            }
        }
        $value = preg_replace('/((?:app[_-]?secret|x-token|authorization|api-sign|member_sign)\s*[=:]\s*)[^\s&,;]+/i', '$1[REDACTED]', $value);
        $value = preg_replace('/(?<![0-9])1[3-9][0-9]{9}(?![0-9])/', '[MASKED_PHONE]', $value);
        // Avoid retaining large HTML/base64 blobs in normal request logs; business identifiers remain intact.
        if (mb_strlen($value, '8bit') > 16384) {
            return ['summary' => '[LARGE_TEXT]', 'bytes' => mb_strlen($value, '8bit'), 'sha256' => hash('sha256', $value)];
        }
        return $value;
    }
}
