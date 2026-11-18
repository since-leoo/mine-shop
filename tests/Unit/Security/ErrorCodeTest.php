<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Security;

use App\Infrastructure\Error\CatalogError;
use App\Infrastructure\Error\CommonError;
use App\Infrastructure\Error\ErrorCode;
use App\Infrastructure\Error\OrderError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Exception\System\Handler\AppExceptionHandler;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\Config\Config;
use Hyperf\Logger\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ErrorCodeTest extends TestCase
{
    public function testCatalogueCodesAreUniqueAndEveryTemplateFormats(): void
    {
        $codes = [];
        foreach (glob(BASE_PATH . '/app/Infrastructure/Error/*Error.php') as $file) {
            $class = 'App\\Infrastructure\\Error\\' . basename($file, '.php');
            self::assertTrue(is_subclass_of($class, ErrorCode::class));
            foreach ($class::cases() as $error) {
                self::assertArrayNotHasKey($error->value, $codes, $class . '::' . $error->name);
                self::assertGreaterThanOrEqual(100000, $error->value);
                $codes[$error->value] = $class;
                preg_match_all('/(?<!%)%(?:\d+\$)?[-+0 \x27#]*\d*(?:\.\d+)?[bcdeEufFgGosxX]/', $error->template(), $matches);
                self::assertNotSame('', $error->message(array_fill(0, count($matches[0]), 1)));
            }
        }
        self::assertNotEmpty($codes);
    }

    public function testFactoriesRetainExceptionTypesCodesParametersAndCauses(): void
    {
        $cause = new \RuntimeException('upstream secret');
        $code = CatalogError::IMAGE_LIMIT_EXCEEDED;
        foreach (['exception' => \DomainException::class, 'invalid' => \InvalidArgumentException::class, 'internal' => \RuntimeException::class] as $method => $type) {
            $error = $code->{$method}([5], $cause);
            self::assertSame($type, $error::class);
            self::assertSame($code->value, $error->getCode());
            self::assertSame('商品主图最多支持 5 张，请删除多余图片。', $error->getMessage());
            self::assertSame($cause, $error->getPrevious());
        }
    }

    public function testBusinessResponseSeparatesProtocolAndBusinessCodes(): void
    {
        $error = new BusinessException(OrderError::NOT_FOUND, data: ['id' => 8], resultCode: ResultCode::NOT_FOUND);
        self::assertSame(['code' => 404, 'message' => '订单不存在', 'data' => ['id' => 8], 'error_code' => OrderError::NOT_FOUND->value], $error->getResponse()->toArray());
        self::assertSame('订单不存在', $error->getMessage());
        self::assertSame(OrderError::NOT_FOUND->value, $error->getCode());
        self::assertArrayNotHasKey('error_code', (new Result())->toArray());
    }

    public function testProductionNeverRevealsInternalErrorEvenWhenItsCodeMatchesCatalogue(): void
    {
        $handler = new AppExceptionHandler(new Config(['debug' => false]), $this->createMock(ContainerInterface::class), $this->createMock(LoggerFactory::class));
        $response = $handler->handleResponse(new \RuntimeException('database password', OrderError::NOT_FOUND->value))->toArray();
        self::assertSame(CommonError::SYSTEM_BUSY->value, $response['error_code']);
        self::assertSame(CommonError::SYSTEM_BUSY->message(), $response['message']);
        $business = $handler->handleResponse(OrderError::NOT_FOUND->exception())->toArray();
        self::assertSame(OrderError::NOT_FOUND->value, $business['error_code']);
        self::assertSame('订单不存在', $business['message']);
    }
}
