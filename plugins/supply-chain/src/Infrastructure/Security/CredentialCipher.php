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

final class CredentialCipher
{
    /** @param array<string, string> $keys Base64-encoded 32-byte keys from deployment configuration. */
    public function __construct(private readonly array $keys, private readonly string $activeKeyId) {}

    public function __debugInfo(): array
    {
        return ['active_key_id' => $this->activeKeyId];
    }

    public function encrypt(#[\SensitiveParameter] string $plaintext, string $context): string
    {
        $key = $this->key($this->activeKeyId);
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, \OPENSSL_RAW_DATA, $nonce, $tag, $context, 16);
        if ($ciphertext === false) {
            throw new \RuntimeException('供应链凭证加密失败');
        }
        return json_encode(['v' => 1, 'key_id' => $this->activeKeyId, 'nonce' => base64_encode($nonce),
            'tag' => base64_encode($tag), 'ciphertext' => base64_encode($ciphertext)], \JSON_THROW_ON_ERROR);
    }

    public function decrypt(#[\SensitiveParameter] string $envelope, string $context): string
    {
        try {
            $data = json_decode($envelope, true, 16, \JSON_THROW_ON_ERROR);
            if (! \is_array($data) || ($data['v'] ?? null) !== 1) {
                throw new \RuntimeException();
            }
            $nonce = base64_decode((string) ($data['nonce'] ?? ''), true);
            $tag = base64_decode((string) ($data['tag'] ?? ''), true);
            $ciphertext = base64_decode((string) ($data['ciphertext'] ?? ''), true);
            if ($nonce === false || mb_strlen($nonce, '8bit') !== 12 || $tag === false || mb_strlen($tag, '8bit') !== 16 || $ciphertext === false) {
                throw new \RuntimeException();
            }
            $plain = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $this->key((string) ($data['key_id'] ?? '')),
                \OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                $context
            );
            if ($plain === false) {
                throw new \RuntimeException();
            }
            return $plain;
        } catch (\Throwable) {
            throw new \RuntimeException('供应链凭证解密失败，请检查部署密钥与连接绑定');
        }
    }

    private function key(string $id): string
    {
        $key = base64_decode($this->keys[$id] ?? '', true);
        if ($key === false || mb_strlen($key, '8bit') !== 32) {
            throw new \RuntimeException('供应链凭证加密密钥未配置或不是 32 字节 Base64 密钥');
        }
        return $key;
    }
}
