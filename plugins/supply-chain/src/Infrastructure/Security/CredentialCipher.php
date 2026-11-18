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

use App\Infrastructure\Error\SupplyChainError;

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
            throw SupplyChainError::CREDENTIAL_ENCRYPTION_FAILED->internal();
        }
        return json_encode(['v' => 1, 'key_id' => $this->activeKeyId, 'nonce' => base64_encode($nonce),
            'tag' => base64_encode($tag), 'ciphertext' => base64_encode($ciphertext)], \JSON_THROW_ON_ERROR);
    }

    public function decrypt(#[\SensitiveParameter] string $envelope, string $context): string
    {
        try {
            $data = json_decode($envelope, true, 16, \JSON_THROW_ON_ERROR);
            if (! \is_array($data) || ($data['v'] ?? null) !== 1) {
                throw SupplyChainError::CREDENTIAL_DECRYPTION_FAILED->internal();
            }
            $nonce = base64_decode((string) ($data['nonce'] ?? ''), true);
            $tag = base64_decode((string) ($data['tag'] ?? ''), true);
            $ciphertext = base64_decode((string) ($data['ciphertext'] ?? ''), true);
            if ($nonce === false || mb_strlen($nonce, '8bit') !== 12 || $tag === false || mb_strlen($tag, '8bit') !== 16 || $ciphertext === false) {
                throw SupplyChainError::CREDENTIAL_DECRYPTION_FAILED->internal();
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
                throw SupplyChainError::CREDENTIAL_DECRYPTION_FAILED->internal();
            }
            return $plain;
        } catch (\Throwable) {
            throw SupplyChainError::CREDENTIAL_DECRYPTION_FAILED->internal();
        }
    }

    private function key(string $id): string
    {
        $key = base64_decode($this->keys[$id] ?? '', true);
        if ($key === false || mb_strlen($key, '8bit') !== 32) {
            throw SupplyChainError::CREDENTIAL_KEY_INVALID->internal();
        }
        return $key;
    }
}
