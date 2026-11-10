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

use Hyperf\Contract\TranslatorInterface;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\Validation\ValidatorFactory;
use Mine\Access\Attribute\Permission;
use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Interface\Admin\SupplyChainController;
use Plugin\SupplyChain\Interface\Dto\ConnectionDto;
use Plugin\SupplyChain\Interface\Request\AfterSaleSubmitRequest;
use Plugin\SupplyChain\Interface\Request\ConnectionRequest;
use Plugin\SupplyChain\Interface\Request\RetryTaskRequest;
use Plugin\SupplyChain\Interface\Request\SupplyQueryRequest;
use Plugin\SupplyChain\Interface\Transformer\SupplyTransformer;

/**
 * @coversNothing
 * @internal
 */
final class AdminBoundaryTest extends TestCase
{
    public function testEveryHttpActionHasExplicitPermissionAndParametersRequireBothPermissions(): void
    {
        $controller = new \ReflectionClass(SupplyChainController::class);
        $actions = 0;
        foreach ($controller->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes(GetMapping::class) === [] && $method->getAttributes(PostMapping::class) === [] && $method->getAttributes(PutMapping::class) === []) {
                continue;
            }
            ++$actions;
            self::assertCount(1, $method->getAttributes(Permission::class), $method->getName());
        }
        self::assertGreaterThanOrEqual(23, $actions);
        $permission = $controller->getMethod('taskParameters')->getAttributes(Permission::class)[0]->newInstance();
        self::assertSame(Permission::OPERATION_AND, $permission->getOperation());
        self::assertSame(['supply-chain:tasks/read', 'supply-chain:logs/params'], $permission->getCode());
        $permission = $controller->getMethod('log')->getAttributes(Permission::class)[0]->newInstance();
        self::assertSame(['supply-chain:logs/read', 'supply-chain:logs/params'], $permission->getCode());
    }

    public function testOrdinaryTaskReadCannotExposeNestedParametersOrExecutionCredentials(): void
    {
        $data = (new SupplyTransformer())->task($this->task());
        $json = json_encode($data, \JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('PRIVATE-', $json);
        self::assertArrayNotHasKey('payload', $data);
        self::assertArrayNotHasKey('result', $data);
        self::assertArrayNotHasKey('request', $data['attempts'][0]);
        self::assertArrayNotHasKey('detail', $data['events'][0]);
        self::assertArrayNotHasKey('request', $data['logs'][0]);
        self::assertSame('第一次调用失败', $data['attempts'][0]['error_message']);
        self::assertFalse($data['retryable']);
        self::assertSame('query', $data['next_action']);
    }

    public function testPrivilegedTaskReadKeepsExecutionHistoryButMasksSecretsAndAddress(): void
    {
        $data = (new SupplyTransformer())->task($this->task(), true);
        self::assertSame('PUBLIC-SKU', $data['payload']['external_sku_id']);
        self::assertSame('[masked]', $data['payload']['address']);
        self::assertSame('[redacted]', $data['payload']['app_secret']);
        self::assertSame('[redacted]', $data['attempts'][0]['request']['token']);
        self::assertArrayNotHasKey('lease_token', $data);
        self::assertArrayNotHasKey('lease_token', $data['attempts'][0]);
        self::assertArrayNotHasKey('request', $data['logs'][0], 'Embedded logs remain summaries even in a task parameter response');
    }

    public function testConnectionResponseNeverIncludesPlaintextCiphertextOrExtraSettings(): void
    {
        $data = (new SupplyTransformer())->connection([
            'id' => 1, 'name' => '供货商', 'app_key' => 'key', 'api_domain' => 'https://supply.example.com',
            'app_secret' => 'PRIVATE-SECRET', 'app_secret_ciphertext' => 'PRIVATE-CIPHER',
            'settings' => ['secret' => 'PRIVATE-SETTINGS'],
        ]);
        self::assertTrue($data['has_app_secret']);
        self::assertSame('https://supply.example.com', $data['api_origin']);
        self::assertStringNotContainsString('PRIVATE-', json_encode($data, \JSON_THROW_ON_ERROR));
    }

    public function testConnectionCarrierMappingHasInputAndOutputAllowlists(): void
    {
        $settings = ['unrelated_secret' => 'PRIVATE-SECRET', 'return_carriers' => ['顺丰' => ['name' => '顺丰速运', 'code' => 'SF', 'token' => 'PRIVATE-TOKEN']]];
        $stored = (new ConnectionDto(['settings' => $settings], null))->toArray();
        self::assertSame(['return_carriers' => ['顺丰' => ['name' => '顺丰速运', 'code' => 'SF']]], $stored['settings']);
        $response = (new SupplyTransformer())->connection(['id' => 1, 'settings' => $settings]);
        self::assertSame(['顺丰' => ['name' => '顺丰速运', 'code' => 'SF']], (array) $response['settings']['return_carriers']);
        self::assertStringNotContainsString('PRIVATE-', json_encode($response, \JSON_THROW_ON_ERROR));
        $empty = (new SupplyTransformer())->connection(['id' => 1]);
        self::assertSame('{}', json_encode($empty['settings']['return_carriers'], \JSON_THROW_ON_ERROR));
        self::assertArrayNotHasKey('settings', (new ConnectionDto(['name' => '名称更新'], null))->toArray(), 'Omitted settings must preserve the stored mapping');
    }

    public function testCarrierMappingRequiresBothSupplierNameAndCode(): void
    {
        $request = $this->getMockBuilder(ConnectionRequest::class)->disableOriginalConstructor()->onlyMethods(['getActionMethod'])->getMock();
        $request->method('getActionMethod')->willReturn('updateConnection');
        $base = ['name' => '供货商', 'api_origin' => 'https://supply.example.com', 'app_key' => 'key', 'mode' => 'pause_new'];
        self::assertTrue($this->factory()->make($base + ['settings' => ['return_carriers' => ['顺丰' => ['name' => '顺丰速运', 'code' => 'SF']]]], $request->rules())->passes());
        foreach ([['name' => '顺丰速运'], ['name' => '顺丰速运', 'code' => ''], ['name' => '顺丰速运', 'code' => ['SF']]] as $carrier) {
            self::assertFalse($this->factory()->make($base + ['settings' => ['return_carriers' => ['顺丰' => $carrier]]], $request->rules())->passes());
        }
    }

    public function testPurchaseDetailOnlyReturnsStableOrderIdentifiersAndLines(): void
    {
        $data = (new SupplyTransformer())->purchase([
            'id' => 1, 'snapshot' => ['address' => 'PRIVATE-ADDRESS'],
            'external_orders' => [[
                'external_order_no' => 'REMOTE-1', 'address' => 'PRIVATE-ADDRESS', 'app_secret' => 'PRIVATE-SECRET',
                'items' => [['external_order_item_id' => 'LINE-1', 'external_sku_id' => 'SKU-1', 'quantity' => 2, 'amount_cents' => 12345, 'phone' => 'PRIVATE-PHONE']],
            ]],
        ]);
        self::assertSame('REMOTE-1', $data['external_order_no']);
        self::assertSame('LINE-1', $data['external_orders'][0]['items'][0]['external_order_item_id']);
        self::assertStringNotContainsString('PRIVATE-', json_encode($data, \JSON_THROW_ON_ERROR));
    }

    public function testSupplierAfterSaleCandidateIsAnObservationRatherThanAnAdoptedIdentifier(): void
    {
        $data = (new SupplyTransformer())->afterSale([
            'id' => 1, 'external_after_sale_id' => null, 'status' => 'unknown',
            'snapshot' => ['candidate_external_after_sale_id' => 'REMOTE-CANDIDATE', 'phone' => 'PRIVATE-PHONE'],
        ]);
        self::assertNull($data['external_after_sale_id']);
        self::assertSame('unknown', $data['status']);
        self::assertSame('REMOTE-CANDIDATE', $data['candidate_external_after_sale_id']);
        self::assertStringNotContainsString('PRIVATE-', json_encode($data, \JSON_THROW_ON_ERROR));
    }

    public function testCatalogKeepsUnknownSelectionStateAndPricesInIntegerCents(): void
    {
        $data = (new SupplyTransformer())->catalog([
            'external_product_id' => '000123', 'name' => '商品', 'main_image' => 'https://image.example/a.png',
            'skus' => [['external_sku_id' => 'S1', 'cost_price' => 12345, 'stock' => 3], ['external_sku_id' => 'S2', 'cost_price' => 10000, 'stock' => 2]],
            'raw_response' => ['token' => 'PRIVATE-TOKEN'],
        ]);
        self::assertNull($data['selected']);
        self::assertSame('000123', $data['external_product_id']);
        self::assertSame(10000, $data['cost_price']);
        self::assertSame(5, $data['stock']);
        self::assertArrayNotHasKey('raw_response', $data);
    }

    public function testConnectionValidationRequiresHttpsOriginAndCreationSecret(): void
    {
        $request = $this->getMockBuilder(ConnectionRequest::class)->disableOriginalConstructor()->onlyMethods(['getActionMethod'])->getMock();
        $request->method('getActionMethod')->willReturn('createConnection');
        $base = ['name' => '供货商', 'api_origin' => 'https://supply.example.com', 'app_key' => 'key', 'app_secret' => 'secret', 'mode' => 'pause_new'];
        self::assertTrue($this->factory()->make($base, $request->rules())->passes());
        foreach (['http://supply.example.com', 'https://user:password@supply.example.com', 'https://supply.example.com/supplyapi', 'https://supply.example.com?secret=a'] as $origin) {
            self::assertFalse($this->factory()->make(array_replace($base, ['api_origin' => $origin]), $request->rules())->passes());
        }
        unset($base['app_secret']);
        self::assertFalse($this->factory()->make($base, $request->rules())->passes());
    }

    public function testQueryValidationRejectsReversedDatesAndUnboundedPageSize(): void
    {
        $request = $this->getMockBuilder(SupplyQueryRequest::class)->disableOriginalConstructor()->onlyMethods(['getActionMethod', 'filled'])->getMock();
        $request->method('getActionMethod')->willReturn('logs');
        $request->method('filled')->willReturn(true);
        self::assertFalse($this->factory()->make(['created_from' => '2026-10-16 10:00:00', 'created_to' => '2026-10-15 10:00:00'], $request->rules())->passes());
        self::assertFalse($this->factory()->make(['page_size' => 1000000], $request->rules())->passes());
        self::assertTrue($this->factory()->make(['page_size' => 50, 'created_from' => '2026-10-16 10:00:00', 'created_to' => '2026-10-16 11:00:00'], $request->rules())->passes());
    }

    public function testAfterSaleRequestRejectsSwapAndRequiresExplicitSupplierCodes(): void
    {
        $request = (new \ReflectionClass(AfterSaleSubmitRequest::class))->newInstanceWithoutConstructor();
        $base = ['external_order_item_id' => 'LINE-1', 'quantity' => 1, 'amount_cents' => 12345, 'reason_type' => 1, 'reason' => '质量问题', 'received_status' => 1, 'refund_type' => 1, 'refund_way' => 1];
        self::assertTrue($this->factory()->make($base, $request->rules())->passes());
        self::assertFalse($this->factory()->make(array_replace($base, ['refund_type' => 2]), $request->rules())->passes());
        self::assertFalse($this->factory()->make(array_replace($base, ['amount_cents' => 1.23]), $request->rules())->passes());
        unset($base['received_status']);
        self::assertFalse($this->factory()->make($base, $request->rules())->passes());
    }

    public function testRetryRequiresNonemptyAuditReason(): void
    {
        $request = (new \ReflectionClass(RetryTaskRequest::class))->newInstanceWithoutConstructor();
        self::assertFalse($this->factory()->make(['reason' => '   '], $request->rules())->passes());
        self::assertTrue($this->factory()->make(['reason' => '余额已补足'], $request->rules())->passes());
    }

    private function factory(): ValidatorFactory
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => $key);
        return new ValidatorFactory($translator);
    }

    private function task(): array
    {
        return [
            'id' => 9, 'type' => 'order_push', 'status' => 'unknown', 'lease_token' => 'PRIVATE-LEASE',
            'payload' => ['external_sku_id' => 'PUBLIC-SKU', 'address' => 'PRIVATE-ADDRESS', 'app_secret' => 'PRIVATE-SECRET'],
            'result' => ['phone' => 'PRIVATE-PHONE'],
            'attempts' => [['id' => 1, 'attempt_no' => 1, 'status' => 'failed', 'error_message' => '第一次调用失败', 'lease_token' => 'PRIVATE-LEASE', 'parameters' => ['token' => 'PRIVATE-TOKEN'], 'result' => ['address' => 'PRIVATE-ADDRESS']]],
            'events' => [['id' => 1, 'action' => 'retry', 'detail' => ['address' => 'PRIVATE-ADDRESS']]],
            'logs' => [['id' => 1, 'status' => 'failed', 'request_data' => ['token' => 'PRIVATE-TOKEN'], 'response_data' => ['address' => 'PRIVATE-ADDRESS']]],
        ];
    }
}
