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

namespace Plugin\WecomScrm\Service;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Plugin\WecomScrm\Job\SyncWecomEmployeesJob;
use Plugin\WecomScrm\Job\SyncWecomTagsJob;

final class WecomCallbackService
{
    public function __construct(private readonly DriverFactory $driverFactory) {}

    public function verify(string $signature, string $timestamp, string $nonce, string $echoStr): string
    {
        $this->assertSignature($signature, $timestamp, $nonce, $echoStr);
        return $this->decrypt($echoStr);
    }

    /** @return array{event:string,change_type:string,xml:string} */
    public function handle(string $signature, string $timestamp, string $nonce, string $body): array
    {
        $document = simplexml_load_string($body, \SimpleXMLElement::class, \LIBXML_NONET | \LIBXML_NOCDATA);
        if (! $document || ! isset($document->Encrypt)) {
            throw new \InvalidArgumentException('企业微信回调 XML 无效');
        }
        $encrypted = (string) $document->Encrypt;
        $this->assertSignature($signature, $timestamp, $nonce, $encrypted);
        $xml = $this->decrypt($encrypted);
        $payload = simplexml_load_string($xml, \SimpleXMLElement::class, \LIBXML_NONET | \LIBXML_NOCDATA);
        if (! $payload) {
            throw new \InvalidArgumentException('企业微信回调消息解密失败');
        }
        $event = mb_strtolower((string) ($payload->Event ?? ''));
        $changeType = mb_strtolower((string) ($payload->ChangeType ?? ''));
        if ($event === 'change_external_tag') {
            $this->push(SyncWecomTagsJob::class);
        } elseif ($event === 'change_contact' && \in_array($changeType, ['create_user', 'update_user', 'delete_user', 'create_party', 'update_party', 'delete_party'], true)) {
            $this->push(SyncWecomEmployeesJob::class);
        }
        return ['event' => $event, 'change_type' => $changeType, 'xml' => $xml];
    }

    private function push(string $job): void
    {
        $this->driverFactory->get('default')->push(new $job());
    }

    private function assertSignature(string $signature, string $timestamp, string $nonce, string $encrypted): void
    {
        $token = (string) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.token', '');
        $values = [$token, $timestamp, $nonce, $encrypted];
        sort($values, \SORT_STRING);
        if ($token === '' || ! hash_equals($signature, sha1(implode('', $values)))) {
            throw new \InvalidArgumentException('企业微信回调签名无效');
        }
    }

    private function decrypt(string $encrypted): string
    {
        $key = base64_decode((string) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.aes_key', '') . '=', true);
        if ($key === false || mb_strlen($key) !== 32) {
            throw new \RuntimeException('企业微信 EncodingAESKey 配置无效');
        }
        $cipher = base64_decode($encrypted, true);
        if ($cipher === false) {
            throw new \InvalidArgumentException('企业微信密文无效');
        }
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, \OPENSSL_RAW_DATA | \OPENSSL_ZERO_PADDING, mb_substr($key, 0, 16));
        if ($plain === false || mb_strlen($plain) < 20) {
            throw new \RuntimeException('企业微信回调解密失败');
        }
        $padding = \ord($plain[mb_strlen($plain) - 1]);
        if ($padding > 0 && $padding <= 32 && mb_substr($plain, -$padding) === str_repeat(\chr($padding), $padding)) {
            $plain = mb_substr($plain, 0, -$padding);
        } else {
            $plain = rtrim($plain, "\0");
        }
        $length = unpack('N', mb_substr($plain, 16, 4))[1] ?? 0;
        $xml = mb_substr($plain, 20, $length);
        if ($xml === '') {
            throw new \RuntimeException('企业微信回调内容为空');
        }
        return $xml;
    }
}
