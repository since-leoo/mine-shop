<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Interface\Api\Request\V1\OrderCommitRequest;
use App\Interface\Api\Request\V1\OrderPreviewRequest;
use Hyperf\Context\Context;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Hyperf\Validation\ValidatorFactory;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderAddressRequestTest extends TestCase
{
    private const ADDRESS = [
        'name' => '测试收货人', 'phone' => '13800000000',
        'province' => '浙江省', 'city' => '杭州市', 'district' => '西湖区', 'detail' => '测试路1号',
        'full_address' => '浙江省杭州市西湖区测试路1号',
    ];

    public static function addressPayloads(): iterable
    {
        foreach ([OrderPreviewRequest::class, OrderCommitRequest::class] as $requestClass) {
            yield $requestClass . '/canonical' => [$requestClass, self::ADDRESS];
            yield $requestClass . '/address book' => [$requestClass, [
                'name' => '测试收货人', 'phone_number' => '13800000000',
                'province_name' => '浙江省', 'city_name' => '杭州市', 'district_name' => '西湖区',
                'detail_address' => '测试路1号', 'address' => self::ADDRESS['full_address'],
            ]];
            yield $requestClass . '/checkout preview' => [$requestClass, [
                'name' => '测试收货人', 'phone' => '13800000000',
                'province' => '浙江省', 'city' => '杭州市', 'district' => '西湖区',
                'detail_address' => '测试路1号',
            ]];
        }
    }

    #[DataProvider('addressPayloads')]
    public function testAddressSurvivesValidationDtoAndOrderSnapshot(string $requestClass, array $address): void
    {
        $request = $this->request($requestClass, ['user_address' => $address]);
        try {
            $dto = $request->toDto(7);
            self::assertSame(self::ADDRESS, OrderAddressValue::fromArray($dto->getUserAddress())->toArray());
        } finally {
            Context::destroy(spl_object_hash($request) . ':' . ValidatorInterface::class);
        }
    }

    public static function invalidAddresses(): iterable
    {
        yield 'only contact details' => [['name' => '测试收货人', 'phone' => '13800000000']];
        yield 'blank street' => [array_replace(self::ADDRESS, ['detail' => '   '])];
        yield 'invalid alias type' => [[
            'name' => '测试收货人', 'phone' => '13800000000',
            'province_name' => ['浙江省'], 'city_name' => '杭州市', 'district_name' => '西湖区',
            'detail_address' => '测试路1号',
        ]];
        yield 'street exceeds database limit' => [array_replace(self::ADDRESS, ['detail' => str_repeat('路', 256)])];
        yield 'empty object' => [[]];
        yield 'wrong container type' => ['测试路1号'];
    }

    #[DataProvider('invalidAddresses')]
    public function testIncompleteOrInvalidSubmittedAddressIsRejected(mixed $address): void
    {
        $request = $this->request(OrderCommitRequest::class, ['user_address' => $address]);
        try {
            $this->expectException(ValidationException::class);
            $request->toDto(7);
        } finally {
            Context::destroy(spl_object_hash($request) . ':' . ValidatorInterface::class);
        }
    }

    public function testOmittedAddressStillAllowsOwnedAddressIdOrDefaultLookup(): void
    {
        foreach ([[], ['address_id' => 3], ['user_address' => null]] as $payload) {
            $request = $this->request(OrderPreviewRequest::class, $payload);
            try {
                $dto = $request->toDto(7);
                self::assertNull($dto->getUserAddress());
                self::assertSame($payload['address_id'] ?? null, $dto->getAddressId());
            } finally {
                Context::destroy(spl_object_hash($request) . ':' . ValidatorInterface::class);
            }
        }
    }

    private function request(string $requestClass, array $payload): OrderPreviewRequest
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => $key);
        $request = $this->getMockBuilder($requestClass)
            ->setConstructorArgs([new TestContainer([ValidatorFactoryInterface::class => new ValidatorFactory($translator)])])
            ->onlyMethods(['all', 'getUploadedFiles', 'getAttribute'])
            ->getMock();
        $request->method('all')->willReturn(array_merge([
            'goods_request_list' => [['sku_id' => 11, 'quantity' => 1]],
            'order_type' => 'normal', 'total_amount' => 600,
        ], $payload));
        $request->method('getUploadedFiles')->willReturn([]);
        $request->method('getAttribute')->willReturn(null);
        return $request;
    }
}
