<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Catalog;

use App\Interface\Api\Request\V1\ProductListRequest;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;

final class ProductSortRequestTest extends TestCase
{
    public function testOnlySupportedPriceDirectionsPassValidation(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key) => $key);
        $factory = new ValidatorFactory($translator);
        $request = (new \ReflectionClass(ProductListRequest::class))->newInstanceWithoutConstructor();
        foreach ([[], ['sort' => 'price_asc'], ['sort' => 'price_desc']] as $params) {
            self::assertTrue($factory->make($params, $request->indexRules())->passes());
        }
        foreach ([['sort' => 1], ['sort' => 'min_price desc'], ['sort' => ['price_asc']]] as $params) {
            self::assertFalse($factory->make($params, $request->indexRules())->passes());
        }
    }
}
