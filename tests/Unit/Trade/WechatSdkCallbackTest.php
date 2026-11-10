<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Infrastructure\Service\Pay\YsdPayService;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yansongda\Pay\Exception\InvalidSignException;

final class WechatSdkCallbackTest extends TestCase
{
    protected function setUp(): void
    {
        $container = new \Hyperf\Di\Container(new \Hyperf\Di\Definition\DefinitionSource([]));
        $container->set(\Psr\Container\ContainerInterface::class, $container);
        $container->set(\Yansongda\Artful\Contract\ContainerInterface::class, $container);
        \Yansongda\Pay\Pay::setContainer($container);
    }

    protected function tearDown(): void
    {
        \Yansongda\Pay\Pay::clear();
    }

    /** Exercises the installed SDK's RSA verification and AES-GCM resource decryption, without HTTP. */
    public function testSignedEncryptedEnvelopeReturnsResource(): void
    {
        [$request, $config, $payload] = $this->fixture();
        self::assertSame($payload, (new YsdPayService())->callback($request, $config));
    }

    public function testTamperedEnvelopeIsRejectedBeforeDomainEntry(): void
    {
        [$request, $config] = $this->fixture();
        $this->expectException(InvalidSignException::class);
        (new YsdPayService())->callback($request->withHeader('Wechatpay-Nonce', 'tampered'), $config);
    }

    private function fixture(): array
    {
        $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $public = openssl_pkey_get_details($private)['key'];
        $secret = str_repeat('k', 32);
        $payload = ['out_trade_no' => 'TEST', 'trade_state' => 'SUCCESS', 'amount' => ['total' => 123, 'currency' => 'CNY']];
        $cipher = openssl_encrypt(json_encode($payload, JSON_THROW_ON_ERROR), 'aes-256-gcm', $secret, OPENSSL_RAW_DATA, '123456789012', $tag, 'transaction');
        $body = json_encode(['id' => 'event-1', 'resource' => [
            'algorithm' => 'AEAD_AES_256_GCM', 'nonce' => '123456789012', 'associated_data' => 'transaction',
            'ciphertext' => base64_encode($cipher . $tag),
        ]], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        openssl_sign($timestamp . "\nnonce\n" . $body . "\n", $signature, $private, OPENSSL_ALGO_SHA256);
        $request = new ServerRequest('POST', 'https://localhost.test/payment/notify', [
            'Wechatpay-Serial' => 'LOCAL_TEST_KEY', 'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => 'nonce', 'Wechatpay-Signature' => base64_encode($signature),
        ], $body);
        return [$request, ['mch_secret_key' => $secret, 'wechat_public_cert_path' => ['LOCAL_TEST_KEY' => $public]], $payload];
    }
}
