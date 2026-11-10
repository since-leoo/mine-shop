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

namespace HyperfTests\Unit\SupplyChain;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Handler\YunzhongClientHandler;
use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\Signer;
use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;
use Plugin\SupplyChain\Infrastructure\Security\LogSanitizer;
use Plugin\SupplyChain\Interfaces\ApiLogRepositoryInterface;
use Plugin\SupplyChain\Interfaces\ConnectionRepositoryInterface;
use Plugin\SupplyChain\Interfaces\Data\Address;
use Plugin\SupplyChain\Interfaces\Data\AfterSaleCommand;
use Plugin\SupplyChain\Interfaces\Data\ProductQuery;
use Plugin\SupplyChain\Interfaces\Data\PurchaseCommand;
use Plugin\SupplyChain\Interfaces\Data\PurchaseLine;
use Plugin\SupplyChain\Interfaces\RequestLimiterInterface;
use Plugin\SupplyChain\Interfaces\TokenStoreInterface;
use Plugin\SupplyChain\Service\ConnectionConfig;
use Plugin\SupplyChain\Service\SupplyChainManager;
use Plugin\SupplyChain\Service\SupplyChainSettingsResolver;

/**
 * Pure protocol tests: no .env, Redis, database, or external HTTP.
 * @internal
 * @coversNothing
 */
final class SdkProtocolTest extends TestCase
{
    public function testPostSignsExactlyTheSentJsonBytesAndWritesIntentFirst(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token-fixture', 300);
        $logs = new SdkMemoryLogs();
        $history = [];
        $mock = new MockHandler([function ($request) use ($logs): Response {
            self::assertCount(1, $logs->intents, 'Intent must exist before any HTTP request');
            self::assertSame('intent', $logs->intents[1]['status']);
            return $this->response(['Orders' => [['order_sn' => 'UP-1'], ['order_sn' => 'UP-2']]]);
        }]);
        $client = $this->client($config, $tokens, $logs, $mock, $history);
        $result = $client->orders()->submit($this->purchase());
        self::assertSame(['UP-1', 'UP-2'], $result['external_order_numbers']);
        self::assertCount(1, $history);
        $request = $history[0]['request'];
        $body = (string) $request->getBody();
        self::assertStringContainsString('\u4e2d', $body);
        self::assertStringContainsString('\/', $body);
        $params = ['Api-App-Key' => $config->appKey, 'Api-Time-Stamp' => $request->getHeaderLine('Api-Time-Stamp'),
            'Api-Nonce' => (new Signer())->nonce($config->appKey, $request->getHeaderLine('Api-Time-Stamp')), 'x-token' => 'token-fixture'];
        self::assertSame((new Signer())->sign($params, $config->appSecret, $body), $request->getHeaderLine('Api-Sign'));
        self::assertSame('', $request->getHeaderLine('Api-Nonce'));
        self::assertTrue($history[0]['options']['verify']);
        self::assertFalse($history[0]['options']['allow_redirects']);
        self::assertSame(15.0, $history[0]['options']['timeout']);
        self::assertSame('success', $logs->outcomes[1]['status']);
        $serialized = json_encode([$logs->intents, $logs->outcomes], \JSON_UNESCAPED_UNICODE);
        foreach ([$config->appSecret, 'token-fixture', '13800138000', '测试收件人', '测试路 42 号'] as $private) {
            self::assertStringNotContainsString($private, $serialized);
        }
        self::assertSame('LOCAL-1', $logs->intents[1]['request_data']['body']['order_sn']);
    }

    public function testExpiredReadRefreshesOnceWithOriginalArgumentsAndNewRequest(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'stale-token', 300);
        $logs = new SdkMemoryLogs();
        $history = [];
        $mock = new MockHandler([$this->response([], 6), $this->response(['token' => 'new-token', 'expiresAt' => (int) ((microtime(true) + 3600) * 1000)]), $this->response(['list' => [], 'total' => 0])]);
        $client = $this->client($config, $tokens, $logs, $mock, $history);
        self::assertSame([], $client->catalog()->search(new ProductQuery(3, 20, '中文/商品'))['items']);
        self::assertCount(3, $history);
        $first = json_decode((string) $history[0]['request']->getBody(), true);
        $last = json_decode((string) $history[2]['request']->getBody(), true);
        self::assertSame(3, $last['page']);
        self::assertSame('中文/商品', $last['title']);
        self::assertNotSame($first['message_id'], $last['message_id']);
        self::assertSame('new-token', $history[2]['request']->getHeaderLine('x-token'));
        self::assertStringNotContainsString('new-token', json_encode($logs->outcomes));
        self::assertSame(1, $tokens->locks);
    }

    public function testExpiredWriteIsNeverAutomaticallyReplayed(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'stale-token', 300);
        $history = [];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([$this->response([], 6)]), $history);
        try {
            $client->orders()->submit($this->purchase());
            self::fail('Expected rejected authentication');
        } catch (\DomainException $error) {
            self::assertSame(6, $error->getCode());
        }
        self::assertCount(1, $history);
        self::assertNull($tokens->get($config->tokenKey()));
    }

    public function testReadTokenRetryStopsAfterSecondExpiry(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'stale', 300);
        $history = [];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([
            $this->response([], 6), $this->response(['token' => 'fresh', 'expiresAt' => (int) ((microtime(true) + 3600) * 1000)]), $this->response([], 6),
        ]), $history);
        try {
            $client->catalog()->search(new ProductQuery());
            self::fail('Expected second expiry to stop');
        } catch (\DomainException $error) {
            self::assertSame(6, $error->getCode());
        }
        self::assertCount(3, $history);
    }

    public function testTransportFailureIsUnknownAndDoesNotLeakExceptionUrl(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'private-token', 300);
        $logs = new SdkMemoryLogs();
        $history = [];
        $error = new ConnectException('url?app_secret=private-secret&x-token=private-token', new Request('POST', 'https://example.test'));
        $client = $this->client($config, $tokens, $logs, new MockHandler([$error]), $history);
        try {
            $client->orders()->submit($this->purchase());
            self::fail('Expected unknown write');
        } catch (\RuntimeException $caught) {
            self::assertSame(YunzhongClient::OUTCOME_UNKNOWN, $caught->getCode());
            self::assertNull($caught->getPrevious());
            self::assertStringNotContainsString('private-secret', $caught->getMessage());
        }
        self::assertCount(1, $history);
        self::assertSame('unknown', $logs->outcomes[1]['status']);
        self::assertStringNotContainsString('private-token', json_encode($logs->outcomes));
    }

    public function testNoHttpWhenIntentCannotBePersisted(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $logs = new SdkMemoryLogs();
        $logs->failBegin = true;
        $history = [];
        $client = $this->client($config, $tokens, $logs, new MockHandler([]), $history);
        try {
            $client->orders()->submit($this->purchase());
            self::fail('Expected audit failure');
        } catch (\RuntimeException $error) {
            self::assertSame(YunzhongClient::AUDIT_UNAVAILABLE, $error->getCode());
        }
        self::assertSame([], $history);
    }

    public function testLogFinalizationFailureRemainsUnknownAfterSuccess(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $logs = new SdkMemoryLogs();
        $logs->failFinish = true;
        $history = [];
        $client = $this->client($config, $tokens, $logs, new MockHandler([$this->response(['Orders' => [['order_sn' => 'UP-1']]])]), $history);
        try {
            $client->orders()->submit($this->purchase());
            self::fail('Expected unknown despite successful HTTP');
        } catch (\RuntimeException $error) {
            self::assertSame(YunzhongClient::OUTCOME_UNKNOWN, $error->getCode());
        }
        self::assertCount(1, $history);
        self::assertCount(1, $logs->intents);
        self::assertSame([], $logs->outcomes);
    }

    public function testGetIncludesBusinessFieldsInSignatureAndUsesCorrectCaseSensitivePath(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'test-token', 300);
        $history = [];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([$this->response(['read' => []]), $this->response([])]), $history);
        self::assertSame(['orders' => []], $client->orders()->findByMerchantOrderNo('A/中文'));
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        $signature = $query['Api-Sign'];
        unset($query['Api-Sign']);
        self::assertSame('A/中文', $query['third_order_sn']);
        self::assertSame((new Signer())->sign($query, $config->appSecret), $signature);
        $client->orders()->logistics('UP-1');
        self::assertSame('/supplyapi/app/order/Logistic', $history[1]['request']->getUri()->getPath());
    }

    public function testProductMoneyAndStockKeepIntegerCentsAndMissingQuantitiesFailClosed(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $history = [];
        $product = ['id' => 101, 'title' => '商品', 'is_display' => 1, 'skus' => [
            ['id' => 102, 'title' => '红色', 'price' => 1234, 'guide_price' => 2000, 'origin_price' => 3000, 'stock' => 2],
        ]];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([
            $this->response(['list' => [$product]]),
            $this->response([['express_no' => 'SF123', 'company_code' => 'SF', 'order_items' => [['sku_id' => 102, 'product_id' => 101]]]]),
        ]), $history);
        $detail = $client->catalog()->details(['101'])[0];
        self::assertSame(1234, $detail['skus'][0]['cost_price']);
        self::assertSame(2000, $detail['skus'][0]['sale_price']);
        self::assertSame('102', $detail['skus'][0]['external_sku_id']);
        self::assertSame([['name' => '默认', 'value' => '红色']], $detail['skus'][0]['spec_values']);
        $this->expectExceptionMessage('send_num');
        $client->orders()->logistics('UP-1');
    }

    public function testCatalogAndDetailsUseProductNameForUnnamedSkuAndDefaultSpecification(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $history = [];
        $sku = ['price' => 100, 'guide_price' => 200, 'origin_price' => 300, 'stock' => 2];
        $product = ['id' => 101, 'title' => '商品', 'is_display' => 1, 'skus' => [
            $sku + ['id' => 102, 'title' => ' 红色 / XL '],
            $sku + ['id' => 103, 'title' => '  '],
            $sku + ['id' => 104],
            $sku + ['id' => 105, 'title' => '0'],
        ]];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([
            $this->response(['list' => [$product], 'total' => 1]),
            $this->response(['list' => [$product]]),
        ]), $history);
        $listed = $client->catalog()->search(new ProductQuery())['items'][0];
        $detail = $client->catalog()->details(['101'])[0];
        foreach ([$listed, $detail] as $normalized) {
            foreach ([['红色 / XL', '红色 / XL'], ['商品', '默认'], ['商品', '默认'], ['0', '0']] as $index => [$title, $specification]) {
                self::assertSame($title, $normalized['skus'][$index]['title']);
                self::assertSame([['name' => '默认', 'value' => $specification]], $normalized['skus'][$index]['spec_values']);
            }
            self::assertSame($product, $normalized['raw_snapshot'], 'Keep original provider fields available for diagnostics');
        }
        self::assertSame('/supplyapi/app/product/storage/list', $history[0]['request']->getUri()->getPath());
        self::assertSame('/supplyapi/app/product/storage/detailList', $history[1]['request']->getUri()->getPath());
        $request = json_decode((string) $history[1]['request']->getBody(), true);
        self::assertSame([101], $request['ids']);
        self::assertSame(['ids', 'message_id'], array_keys($request));
        self::assertNotEmpty($request['message_id']);
    }

    public function testMessagesNeverAutoAcknowledgeAndPreserveStockChanges(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $history = [];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([$this->response(['list' => [
            ['id' => 77, 'type_string' => 'goods.update', 'content' => json_encode(['product_id' => 101, 'is_stock' => 1, 'member_sign' => 'obsolete-secret'])],
        ]])]), $history);
        $messages = $client->messages()->pull();
        self::assertSame(1, $messages[0]['payload']['is_stock']);
        self::assertArrayNotHasKey('member_sign', $messages[0]['payload']);
        self::assertCount(1, $history);
    }

    public function testUnverifiedCancellationDoesNotCallCloudWarehouseEndpoint(): void
    {
        $config = $this->config();
        $history = [];
        $client = $this->client($config, new SdkMemoryTokens(), new SdkMemoryLogs(), new MockHandler([]), $history);
        try {
            $client->orders()->cancel('LOCAL-1');
            self::fail('Expected explicit unsupported operation');
        } catch (\DomainException $error) {
            self::assertStringContainsString('禁止套用云仓', $error->getMessage());
        }
        self::assertSame([], $history);
    }

    public function testCipherAuthenticatesConnectionAndSupportsRotation(): void
    {
        $key1 = base64_encode(str_repeat('a', 32));
        $key2 = base64_encode(str_repeat('b', 32));
        $old = new CredentialCipher(['old' => $key1], 'old');
        $cipher = $old->encrypt('private-secret', 'supply-connection:1');
        self::assertStringNotContainsString('private-secret', $cipher);
        self::assertNotSame($cipher, $old->encrypt('private-secret', 'supply-connection:1'));
        $rotated = new CredentialCipher(['old' => $key1, 'new' => $key2], 'new');
        self::assertSame('private-secret', $rotated->decrypt($cipher, 'supply-connection:1'));
        self::assertSame('new', json_decode($rotated->encrypt('secret', 'supply-connection:1'), true)['key_id']);
        $this->expectException(\RuntimeException::class);
        $rotated->decrypt($cipher, 'supply-connection:2');
    }

    public function testCipherRefusesMissingDeploymentKeyAndTampering(): void
    {
        try {
            (new CredentialCipher([], 'primary'))->encrypt('secret', 'supply-connection:1');
            self::fail('Expected missing key failure');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString('未配置', $error->getMessage());
        }
        $cipher = new CredentialCipher(['test' => base64_encode(str_repeat('k', 32))], 'test');
        $envelope = json_decode($cipher->encrypt('secret', 'supply-connection:1'), true);
        $envelope['ciphertext'] = base64_encode('corrupt');
        $this->expectException(\RuntimeException::class);
        $cipher->decrypt(json_encode($envelope), 'supply-connection:1');
    }

    public function testCipherMeasuresBinaryKeyNonceAndTagInBytes(): void
    {
        // 32 bytes but 16 Unicode characters: crypto lengths must not depend on UTF-8 decoding.
        $cipher = new CredentialCipher(['binary' => base64_encode(str_repeat("\xC3\xA9", 16))], 'binary');
        $encrypted = $cipher->encrypt('中文密钥测试', 'supply-connection:1');
        self::assertSame('中文密钥测试', $cipher->decrypt($encrypted, 'supply-connection:1'));
    }

    public function testManagerReloadsConfigurationAndIsolatesTwoConnections(): void
    {
        $cipher = new CredentialCipher(['test' => base64_encode(str_repeat('k', 32))], 'test');
        $repository = new class implements ConnectionRepositoryInterface {
            public array $rows = [];

            public function get(int $connectionId): array
            {
                return $this->rows[$connectionId];
            }
        };
        foreach ([1, 2] as $id) {
            $repository->rows[$id] = ['id' => $id, 'api_domain' => 'https://supplier' . $id . '.test', 'app_key' => 'application' . $id,
                'app_secret_ciphertext' => $cipher->encrypt('secret' . $id, 'supply-connection:' . $id), 'enabled' => true, 'credential_version' => 1];
        }
        $seen = [];
        $manager = new SupplyChainManager(new SupplyChainSettingsResolver($repository, $cipher), function (ConnectionConfig $config) use (&$seen): YunzhongClientHandler {
            $seen[] = $config;
            $history = [];
            return $this->client($config, new SdkMemoryTokens(), new SdkMemoryLogs(), new MockHandler([]), $history);
        });
        self::assertSame([], $seen);
        $manager->forConnection(1);
        $manager->forConnection(2);
        $repository->rows[1]['credential_version'] = 2;
        $manager->forConnection(1);
        self::assertSame([1, 2, 1], array_column($seen, 'id'));
        self::assertSame('secret1', $seen[0]->appSecret);
        self::assertSame('secret2', $seen[1]->appSecret);
        self::assertNotSame($seen[0]->tokenKey(), $seen[2]->tokenKey());
    }

    public function testLegacyNonceAndCanonicalSignatureHandleChineseNullAndScalarTypes(): void
    {
        $signer = new Signer();
        self::assertSame(md5('1700000000000'), $signer->nonce('application4', '1700000000000'));
        self::assertSame(md5('1700000000042'), $signer->nonce('42', '1700000000000'));
        $parameters = ['z' => null, 'a' => '中文', 'n' => 2, 'f' => false];
        $body = json_encode(['nested' => ['a/b', null, true, 1, '1']], \JSON_THROW_ON_ERROR);
        $expected = mb_strtoupper(md5(sha1('a中文fn2zsecret' . $body)));
        self::assertSame($expected, $signer->sign($parameters, 'secret', $body));
        $this->expectException(\InvalidArgumentException::class);
        $signer->sign(['array' => [1, 2]], 'secret');
    }

    public function testSanitizerRedactsNestedCredentialsButRetainsOperationalParameters(): void
    {
        $clean = (new LogSanitizer())->sanitize(['ids' => [1, 2, 3], 'quantity' => 4,
            'nested' => ['app_secret' => 'secret-value', 'member_sign' => 'legacy', 'Authorization' => 'Bearer abc'],
            'msg' => 'provider echoed secret-value', 'phone' => '13800138000'], ['secret-value']);
        self::assertSame([1, 2, 3], $clean['ids']);
        self::assertSame(4, $clean['quantity']);
        self::assertSame('[REDACTED]', $clean['nested']['app_secret']);
        self::assertSame('[MASKED]', $clean['phone']);
        self::assertSame('provider echoed [REDACTED]', $clean['msg']);
    }

    public function testConcurrentTokenRefreshDoesNotStartAnotherHttpCall(): void
    {
        $tokens = new SdkMemoryTokens();
        $tokens->lockAllowed = false;
        $history = [];
        $client = $this->client($this->config(), $tokens, new SdkMemoryLogs(), new MockHandler([]), $history);
        try {
            $client->catalog()->search(new ProductQuery());
            self::fail('Expected refresh contention');
        } catch (\RuntimeException $error) {
            self::assertSame(429, $error->getCode());
        }
        self::assertSame([], $history);
    }

    public function testLateExpiredResponseCannotDeleteANewerToken(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'stale-token', 300);
        $history = [];
        $mock = new MockHandler([function () use ($config, $tokens): Response {
            $tokens->put($config->tokenKey(), 'concurrently-refreshed', 300);
            return $this->response([], 6);
        }, $this->response(['list' => [], 'total' => 0])]);
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), $mock, $history);
        $client->catalog()->search(new ProductQuery());
        self::assertSame('concurrently-refreshed', $tokens->get($config->tokenKey()));
        self::assertSame('concurrently-refreshed', $history[1]['request']->getHeaderLine('x-token'));
        self::assertSame(0, $tokens->locks);
    }

    public function testMalformedBusinessCodeNeverBecomesSuccess(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $history = [];
        $logs = new SdkMemoryLogs();
        $client = $this->client($config, $tokens, $logs, new MockHandler([
            new Response(200, [], '{"code":0.1,"data":{"Orders":[{"order_sn":"fake"}]}}'),
        ]), $history);
        try {
            $client->orders()->submit($this->purchase());
            self::fail('Expected invalid protocol code');
        } catch (\RuntimeException $error) {
            self::assertSame(YunzhongClient::OUTCOME_UNKNOWN, $error->getCode());
        }
        self::assertSame('unknown', $logs->outcomes[1]['status']);
    }

    public function testMalformedSuccessfulPurchaseIsUnknownAndNeverRetried(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $history = [];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([$this->response([])]), $history);
        try {
            $client->orders()->submit($this->purchase());
            self::fail('Expected unconfirmed purchase');
        } catch (\RuntimeException $error) {
            self::assertSame(YunzhongClient::OUTCOME_UNKNOWN, $error->getCode());
        }
        self::assertCount(1, $history);
    }

    public function testAfterSaleTypesUseActualOrderItemAndCreateUsesCents(): void
    {
        $config = $this->config();
        $tokens = new SdkMemoryTokens();
        $tokens->put($config->tokenKey(), 'token', 300);
        $history = [];
        $client = $this->client($config, $tokens, new SdkMemoryLogs(), new MockHandler([
            $this->response(['0' => '退款', '1' => '退货退款']), $this->response(321),
        ]), $history);
        self::assertSame(['退款', '退货退款'], $client->afterSales()->types('201'));
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        self::assertSame('201', $query['order_item_id']);
        self::assertSame('/supplyapi/app/afterSales/getAfterSalesTypeNameMap', $history[0]['request']->getUri()->getPath());
        $command = new AfterSaleCommand('LOCAL-1', '201', 2, 2468, 100, 0, 5, '测试原因', '测试说明', 0, 0, 1);
        self::assertSame('321', $client->afterSales()->create($command));
        $payload = json_decode((string) $history[1]['request']->getBody(), true);
        self::assertSame(2468, $payload['amount']);
        self::assertSame(100, $payload['freight']);
        self::assertSame(201, $payload['order_item_id']);
        self::assertSame(2, $payload['num']);
    }

    public function testConfigurationCannotLeakThroughJsonOrQueueSerialization(): void
    {
        self::assertStringNotContainsString('private-secret', json_encode($this->config()));
        $this->expectException(\LogicException::class);
        serialize($this->config());
    }

    private function config(): ConnectionConfig
    {
        return new ConnectionConfig(1, 'https://supplier.test/supplyapi', 'application4', 'private-secret');
    }

    private function purchase(): PurchaseCommand
    {
        return new PurchaseCommand(
            'LOCAL-1',
            [new PurchaseLine('102', 2)],
            new Address('测试收件人', '13800138000', '广东省', '深圳市', '南山区', '测试路 42 号'),
            '中文/备注'
        );
    }

    private function response(mixed $data, int $code = 0): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => $code, 'msg' => $code === 0 ? '成功' : '令牌过期', 'data' => $data], \JSON_THROW_ON_ERROR));
    }

    private function client(ConnectionConfig $config, TokenStoreInterface $tokens, ApiLogRepositoryInterface $logs, MockHandler $mock, array &$history): YunzhongClientHandler
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $limiter = new class implements RequestLimiterInterface {
            public function acquire(int $connectionId, int $leaseSeconds): string
            {
                return 'lease';
            }

            public function release(int $connectionId, string $lease): void {}
        };
        return new YunzhongClientHandler(new YunzhongClient($config, new Client(['handler' => $stack]), $tokens, $logs, $limiter));
    }
}

final class SdkMemoryTokens implements TokenStoreInterface
{
    public array $tokens = [];

    public int $locks = 0;

    public bool $lockAllowed = true;

    public function get(string $key): ?string
    {
        return $this->tokens[$key] ?? null;
    }

    public function put(string $key, string $token, int $ttl): void
    {
        $this->tokens[$key] = $token;
    }

    public function forgetIfEquals(string $key, string $token): void
    {
        if (($this->tokens[$key] ?? null) === $token) {
            unset($this->tokens[$key]);
        }
    }

    public function lock(string $key, string $owner, int $ttl): bool
    {
        ++$this->locks;
        return $this->lockAllowed;
    }

    public function unlock(string $key, string $owner): void {}
}

final class SdkMemoryLogs implements ApiLogRepositoryInterface
{
    public array $intents = [];

    public array $outcomes = [];

    public bool $failBegin = false;

    public bool $failFinish = false;

    public function begin(array $intent): int|string
    {
        if ($this->failBegin) {
            throw new \RuntimeException('database unavailable');
        }
        $id = \count($this->intents) + 1;
        $this->intents[$id] = $intent;
        return $id;
    }

    public function finish(int|string $id, array $outcome): void
    {
        if ($this->failFinish) {
            throw new \RuntimeException('database unavailable');
        }
        $this->outcomes[$id] = $outcome;
    }
}
