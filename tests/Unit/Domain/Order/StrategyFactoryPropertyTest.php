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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use PHPUnit\Framework\TestCase;

/**
 * Feature: order-checkout-refactor, Property 7: 策略工厂注册与查找.
 *
 * For any 传入构造函数的策略数组，OrderTypeStrategyFactory::make() 应能通过每个策略的 type() 值
 * 正确返回对应策略实例。For any 未注册的类型字符串，make() 应抛出 RuntimeException。
 *
 * **Validates: Requirements 8.1, 8.2, 8.3**
 *
 * @internal
 * @coversNothing
 */
final class StrategyFactoryPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    /**
     * Property 7a: For any set of strategies passed to the constructor,
     * make() returns the correct strategy instance for each registered type.
     *
     * Validates: Requirements 8.1, 8.2
     */
    public function testMakeReturnsCorrectStrategyForEachRegisteredType(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate a random number of strategies (1 to 10)
            $count = random_int(1, 10);
            $strategies = [];
            $typeNames = [];

            for ($j = 0; $j < $count; ++$j) {
                $typeName = $this->randomUniqueTypeName($typeNames);
                $typeNames[] = $typeName;
                $strategies[] = $this->createMockStrategy($typeName);
            }

            $factory = new OrderTypeStrategyFactory($strategies);

            // Verify each registered strategy can be retrieved by its type
            foreach ($strategies as $index => $strategy) {
                $result = $factory->make($typeNames[$index]);
                self::assertSame(
                    $strategy,
                    $result,
                    \sprintf(
                        'Iteration %d: make("%s") should return the exact strategy instance registered for that type',
                        $i,
                        $typeNames[$index],
                    ),
                );
                // Also verify the returned strategy's type() matches
                self::assertSame(
                    $typeNames[$index],
                    $result->type(),
                    \sprintf(
                        'Iteration %d: Returned strategy type() should match the requested type "%s"',
                        $i,
                        $typeNames[$index],
                    ),
                );
            }
        }

        self::assertTrue(true, 'All registered-type lookup iterations passed');
    }

    /**
     * Property 7b: For any unregistered type string, make() throws RuntimeException
     * containing the type name in the message.
     *
     * Validates: Requirement 8.3
     */
    public function testMakeThrowsRuntimeExceptionForUnregisteredType(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate a random set of registered strategies
            $count = random_int(0, 5);
            $strategies = [];
            $typeNames = [];

            for ($j = 0; $j < $count; ++$j) {
                $typeName = $this->randomUniqueTypeName($typeNames);
                $typeNames[] = $typeName;
                $strategies[] = $this->createMockStrategy($typeName);
            }

            $factory = new OrderTypeStrategyFactory($strategies);

            // Generate a type name that is NOT in the registered set
            $unregisteredType = $this->randomUniqueTypeName($typeNames);

            try {
                $factory->make($unregisteredType);
                self::fail(\sprintf(
                    'Iteration %d: Expected RuntimeException for unregistered type "%s" (registered: [%s])',
                    $i,
                    $unregisteredType,
                    implode(', ', $typeNames),
                ));
            } catch (\RuntimeException $e) {
                // Verify the exception message contains the unregistered type name
                self::assertStringContainsString(
                    $unregisteredType,
                    $e->getMessage(),
                    \sprintf(
                        'Iteration %d: RuntimeException message should contain the unregistered type name "%s"',
                        $i,
                        $unregisteredType,
                    ),
                );
            }
        }

        self::assertTrue(true, 'All unregistered-type exception iterations passed');
    }

    /**
     * Property 7c: Duplicate type names — the last strategy registered for a given type wins.
     *
     * For any set of strategies where multiple share the same type(), the factory
     * should store the last one (since the constructor iterates in order and overwrites).
     */
    public function testDuplicateTypeLastOneWins(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $sharedType = $this->randomTypeName();

            // Create two different strategy instances with the same type
            $first = $this->createMockStrategy($sharedType);
            $second = $this->createMockStrategy($sharedType);

            $factory = new OrderTypeStrategyFactory([$first, $second]);

            $result = $factory->make($sharedType);
            self::assertSame(
                $second,
                $result,
                \sprintf(
                    'Iteration %d: When duplicate type "%s" is registered, the last strategy should win',
                    $i,
                    $sharedType,
                ),
            );
        }

        self::assertTrue(true, 'All duplicate-type iterations passed');
    }

    /**
     * Create an anonymous implementation of OrderTypeStrategyInterface with the given type name.
     */
    private function createMockStrategy(string $typeName): OrderTypeStrategyInterface
    {
        return new class($typeName) implements OrderTypeStrategyInterface {
            public function __construct(private readonly string $typeName) {}

            public function type(): string
            {
                return $this->typeName;
            }

            public function validate(OrderEntity $orderEntity): void {}

            public function buildDraft(OrderEntity $orderEntity): OrderEntity
            {
                return $orderEntity;
            }

            public function applyCoupon(OrderEntity $orderEntity, array $couponList): void {}

            public function adjustPrice(OrderEntity $orderEntity): void {}

            public function postCreate(OrderEntity $orderEntity): void {}
        };
    }

    /**
     * Generate a random type name string.
     * Uses a mix of lowercase letters and underscores to simulate realistic type identifiers.
     */
    private function randomTypeName(): string
    {
        $length = random_int(3, 20);
        $chars = 'abcdefghijklmnopqrstuvwxyz_';
        $name = '';
        for ($i = 0; $i < $length; ++$i) {
            $name .= $chars[random_int(0, mb_strlen($chars) - 1)];
        }
        // Ensure it starts with a letter (not underscore)
        $name[0] = \chr(random_int(\ord('a'), \ord('z')));
        return $name;
    }

    /**
     * Generate a random type name that is unique among the given existing names.
     *
     * @param string[] $existingNames
     */
    private function randomUniqueTypeName(array $existingNames): string
    {
        $maxAttempts = 100;
        for ($attempt = 0; $attempt < $maxAttempts; ++$attempt) {
            $name = $this->randomTypeName();
            if (! \in_array($name, $existingNames, true)) {
                return $name;
            }
        }
        // Fallback: append a unique suffix
        return $this->randomTypeName() . '_' . bin2hex(random_bytes(4));
    }
}
