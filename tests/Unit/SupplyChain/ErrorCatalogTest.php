<?php

declare(strict_types=1);

namespace HyperfTests\Unit\SupplyChain;

use App\Infrastructure\Error\SupplyChainError;
use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Handler\Yunzhong\PayloadMapper;
use Plugin\SupplyChain\Service\ConnectionConfig;

final class ErrorCatalogTest extends TestCase
{
    public function testParameterizedValidationKeepsItsExceptionTypeAndChineseMessage(): void
    {
        try {
            PayloadMapper::ids([], 12);
            self::fail('Invalid batch must fail');
        } catch (\InvalidArgumentException $error) {
            self::assertSame(SupplyChainError::BATCH_SIZE_INVALID->value, $error->getCode());
            self::assertSame('供应链批次大小必须为 1 至 12', $error->getMessage());
        }
    }

    public function testMalformedProviderDataRemainsAnInternalException(): void
    {
        try {
            PayloadMapper::integer(-1, 'stock');
            self::fail('Malformed upstream stock must fail');
        } catch (\RuntimeException $error) {
            self::assertSame(SupplyChainError::RESPONSE_INTEGER_INVALID->value, $error->getCode());
            self::assertSame('供应链响应字段不是非负整数：stock', $error->getMessage());
        }
    }

    public function testCredentialSerializationStillThrowsLogicException(): void
    {
        $config = new ConnectionConfig(id: 1, baseUrl: 'https://example.test', appKey: 'key', appSecret: 'secret');
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(SupplyChainError::CREDENTIAL_SERIALIZATION_FORBIDDEN->value);
        serialize($config);
    }
}
