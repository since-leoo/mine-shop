<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Promotion;

use App\Interface\Api\Request\V1\Seckill\SeckillPromotionRequest;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;

final class SeckillPromotionRequestTest extends TestCase
{
    public function testIdentifiersAndPageLimitAreActuallyValidated(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key) => $key);
        $factory = new ValidatorFactory($translator);
        $request = (new \ReflectionClass(SeckillPromotionRequest::class))->newInstanceWithoutConstructor();
        foreach ([[], ['activity_id' => '1', 'session_id' => '2', 'limit' => '100'], ['promotion_id' => '1']] as $params) {
            self::assertTrue($factory->make($params, $request->indexRules())->passes());
        }
        foreach ([['activity_id' => 0], ['session_id' => -1], ['session_id' => 'bad'], ['limit' => 101], ['limit' => 0]] as $params) {
            self::assertFalse($factory->make($params, $request->indexRules())->passes());
        }
    }
}
