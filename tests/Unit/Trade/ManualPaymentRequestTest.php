<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Interface\Admin\Controller\Order\OrderController;
use App\Interface\Admin\Request\Order\OrderRequest;
use Hyperf\Context\Context;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Hyperf\Validation\ValidatorFactory;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ManualPaymentRequestTest extends TestCase
{
    public function testManualPaymentUsesRouteAndAuthenticatedIdentityAndIgnoresForgedPaymentFields(): void
    {
        $request = $this->request([
            'order_id' => 999, 'operator_id' => 999, 'operator_name' => '伪造管理员',
            'pay_amount' => 1, 'paid_amount' => 1, 'pay_method' => 'balance',
            'remark' => '  线下收款已核实  ',
        ]);

        try {
            self::assertSame(['remark' => '  线下收款已核实  '], $request->validated());
            $dto = $request->toManualPayDto(123, 42, '当前管理员');
            self::assertSame([
                'order_id' => 123, 'remark' => '线下收款已核实',
                'operator_id' => 42, 'operator_name' => '当前管理员',
            ], get_object_vars($dto));
        } finally {
            Context::destroy(spl_object_hash($request) . ':' . ValidatorInterface::class);
        }
    }

    #[DataProvider('invalidRemarks')]
    public function testInvalidRemarkIsRejectedBeforeCreatingDto(mixed $remark): void
    {
        $request = $this->request(['remark' => $remark]);
        try {
            $this->expectException(ValidationException::class);
            $request->toManualPayDto(123, 42, '当前管理员');
        } finally {
            Context::destroy(spl_object_hash($request) . ':' . ValidatorInterface::class);
        }
    }

    public static function invalidRemarks(): array
    {
        return ['array' => [['invalid']], 'number' => [123], 'too long' => [str_repeat('备', 501)]];
    }

    public function testFiveHundredCharacterRemarkIsAccepted(): void
    {
        $remark = str_repeat('备', 500);
        $request = $this->request(['remark' => $remark]);
        try {
            self::assertSame($remark, $request->toManualPayDto(123, 42, '当前管理员')->getRemark());
        } finally {
            Context::destroy(spl_object_hash($request) . ':' . ValidatorInterface::class);
        }
    }

    private function request(array $payload): OrderRequest
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => $key);
        $request = $this->getMockBuilder(OrderRequest::class)
            ->setConstructorArgs([new TestContainer([ValidatorFactoryInterface::class => new ValidatorFactory($translator)])])
            ->onlyMethods(['all', 'getUploadedFiles', 'getAttribute'])
            ->getMock();
        $request->method('all')->willReturn($payload);
        $request->method('getUploadedFiles')->willReturn([]);
        $request->method('getAttribute')->with(Dispatched::class)->willReturn(
            new Dispatched([1, new Handler([OrderController::class, 'manualPay'], '/test'), []]),
        );
        return $request;
    }
}
