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

namespace HyperfTests\Unit\Interface\Order;

use App\Interface\Api\Request\V1\OrderPreviewRequest;
use PHPUnit\Framework\TestCase;

/**
 * Feature: order-checkout-refactor, Property 6: 地址子字段超长时验证失败.
 *
 * For any user_address 子字段（name, phone, province, city, district, detail）超过规定最大长度的请求，验证应失败。
 *
 * Since Hyperf Request validation requires the full container/HTTP context which is complex
 * in unit tests, we verify this property structurally:
 * 1. Using reflection to instantiate the Request class without constructor
 * 2. Calling rules() to get the validation rules
 * 3. Verifying that each user_address sub-field has the correct 'max:N' rule
 * 4. Verifying that each sub-field has 'string' type rule
 * 5. For the property-based aspect: generate random strings exceeding the max length for each
 *    field and verify the rules would catch them
 *
 * **Validates: Requirements 7.5**
 *
 * @internal
 * @coversNothing
 */
final class AddressFieldLengthPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    /**
     * Expected max lengths per user_address sub-field, aligned with database column sizes.
     */
    private const ADDRESS_FIELD_MAX_LENGTHS = [
        'name' => 60,
        'phone' => 20,
        'province' => 30,
        'city' => 30,
        'district' => 30,
        'detail' => 200,
    ];

    // ---------------------------------------------------------------
    // Part A: Each user_address sub-field has the correct max:N rule
    // ---------------------------------------------------------------

    /**
     * Property 6a: OrderPreviewRequest declares max length rules for every
     * user_address sub-field with the correct limit.
     *
     * Validates: Requirement 7.5
     */
    public function testEachAddressSubFieldHasCorrectMaxRule(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        foreach (self::ADDRESS_FIELD_MAX_LENGTHS as $field => $maxLength) {
            $ruleKey = "user_address.{$field}";

            self::assertArrayHasKey(
                $ruleKey,
                $rules,
                "OrderPreviewRequest must declare rules for '{$ruleKey}'"
            );

            $fieldRules = $this->normalizeRules($rules[$ruleKey]);

            $expectedMaxRule = "max:{$maxLength}";
            self::assertContains(
                $expectedMaxRule,
                $fieldRules,
                "'{$ruleKey}' must have '{$expectedMaxRule}' rule (Requirement 7.5)"
            );
        }
    }

    // ---------------------------------------------------------------
    // Part B: Each user_address sub-field has 'string' type rule
    // ---------------------------------------------------------------

    /**
     * Property 6b: OrderPreviewRequest declares 'string' type rule for every
     * user_address sub-field.
     *
     * Validates: Requirement 7.5
     */
    public function testEachAddressSubFieldHasStringTypeRule(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        foreach (self::ADDRESS_FIELD_MAX_LENGTHS as $field => $maxLength) {
            $ruleKey = "user_address.{$field}";

            self::assertArrayHasKey(
                $ruleKey,
                $rules,
                "OrderPreviewRequest must declare rules for '{$ruleKey}'"
            );

            $fieldRules = $this->normalizeRules($rules[$ruleKey]);

            self::assertContains(
                'string',
                $fieldRules,
                "'{$ruleKey}' must have 'string' type rule (Requirement 7.5)"
            );
        }
    }

    // ---------------------------------------------------------------
    // Part C: user_address parent field is declared as nullable|array
    // ---------------------------------------------------------------

    /**
     * Property 6c: OrderPreviewRequest declares user_address as nullable|array,
     * ensuring the parent field is properly typed before sub-field rules apply.
     *
     * Validates: Requirement 7.5
     */
    public function testUserAddressParentFieldIsNullableArray(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('user_address', $rules, 'OrderPreviewRequest must declare rules for user_address');

        $fieldRules = $this->normalizeRules($rules['user_address']);

        self::assertContains('nullable', $fieldRules, 'user_address must have "nullable" rule');
        self::assertContains('array', $fieldRules, 'user_address must have "array" rule');
    }

    // ---------------------------------------------------------------
    // Part D: Property-based - random over-length strings for each field
    //         would be caught by the max:N rules
    // ---------------------------------------------------------------

    /**
     * Property 6d: For any random string exceeding the max length of each
     * user_address sub-field, the validation rules contain a max:N rule
     * that would reject the over-length value.
     *
     * We generate 100 iterations. In each iteration, we pick a random sub-field,
     * generate a random string that exceeds its max length, and verify the rules
     * would catch it.
     *
     * Validates: Requirement 7.5
     */
    public function testRandomOverLengthStringsWouldBeRejectedByRules(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $fields = array_keys(self::ADDRESS_FIELD_MAX_LENGTHS);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Pick a random sub-field
            $field = $fields[random_int(0, \count($fields) - 1)];
            $maxLength = self::ADDRESS_FIELD_MAX_LENGTHS[$field];
            $ruleKey = "user_address.{$field}";

            // Generate a random string that exceeds the max length
            $overLength = random_int($maxLength + 1, $maxLength + 500);
            $overLengthString = $this->randomString($overLength);

            // Verify the generated string actually exceeds the limit
            self::assertGreaterThan(
                $maxLength,
                mb_strlen($overLengthString),
                "Iteration {$i}: Generated string for '{$field}' must exceed max length {$maxLength}"
            );

            // Verify the rules contain max:N that would catch this
            $fieldRules = $this->normalizeRules($rules[$ruleKey]);
            $expectedMaxRule = "max:{$maxLength}";

            self::assertContains(
                $expectedMaxRule,
                $fieldRules,
                "Iteration {$i}: '{$ruleKey}' must have '{$expectedMaxRule}' rule to reject string of length {$overLength}"
            );

            // Verify 'string' type rule is present (max rule applies to string length)
            self::assertContains(
                'string',
                $fieldRules,
                "Iteration {$i}: '{$ruleKey}' must have 'string' rule so max:N applies to string length"
            );
        }
    }

    // ---------------------------------------------------------------
    // Part E: Property-based - each field individually tested with
    //         random over-length strings (100 iterations per field)
    // ---------------------------------------------------------------

    /**
     * Property 6e: For each user_address sub-field, generate 100 random strings
     * that exceed the max length and verify the rules would catch every one.
     *
     * This ensures comprehensive coverage across all fields, not just random sampling.
     *
     * Validates: Requirement 7.5
     */
    public function testEveryFieldWithRandomOverLengthStrings(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        foreach (self::ADDRESS_FIELD_MAX_LENGTHS as $field => $maxLength) {
            $ruleKey = "user_address.{$field}";
            $fieldRules = $this->normalizeRules($rules[$ruleKey]);
            $expectedMaxRule = "max:{$maxLength}";

            for ($i = 0; $i < self::ITERATIONS; ++$i) {
                // Generate a string that exceeds the max length by a random amount (1 to 300)
                $excess = random_int(1, 300);
                $overLengthString = $this->randomString($maxLength + $excess);

                self::assertGreaterThan(
                    $maxLength,
                    mb_strlen($overLengthString),
                    "Field '{$field}', iteration {$i}: string must exceed max length {$maxLength}"
                );

                self::assertContains(
                    $expectedMaxRule,
                    $fieldRules,
                    "Field '{$field}', iteration {$i}: must have '{$expectedMaxRule}' to reject string of length " . mb_strlen($overLengthString)
                );

                self::assertContains(
                    'string',
                    $fieldRules,
                    "Field '{$field}', iteration {$i}: must have 'string' rule"
                );
            }
        }
    }

    // ---------------------------------------------------------------
    // Part F: Property-based - max length extracted from rules matches
    //         the expected value for randomly selected fields
    // ---------------------------------------------------------------

    /**
     * Property 6f: For any randomly selected user_address sub-field, the max:N
     * value extracted from the rules matches the expected max length constant.
     *
     * This verifies the actual numeric limit in the rules, not just the presence
     * of a max rule.
     *
     * Validates: Requirement 7.5
     */
    public function testExtractedMaxLengthMatchesExpectedForRandomFields(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $fields = array_keys(self::ADDRESS_FIELD_MAX_LENGTHS);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Pick a random sub-field
            $field = $fields[random_int(0, \count($fields) - 1)];
            $expectedMax = self::ADDRESS_FIELD_MAX_LENGTHS[$field];
            $ruleKey = "user_address.{$field}";

            $fieldRules = $this->normalizeRules($rules[$ruleKey]);

            // Extract the actual max value from the rules
            $actualMax = null;
            foreach ($fieldRules as $rule) {
                if (preg_match('/^max:(\d+)$/', $rule, $matches)) {
                    $actualMax = (int) $matches[1];
                    break;
                }
            }

            self::assertNotNull(
                $actualMax,
                "Iteration {$i}: '{$ruleKey}' must have a max:N rule"
            );

            self::assertSame(
                $expectedMax,
                $actualMax,
                "Iteration {$i}: '{$ruleKey}' max rule should be max:{$expectedMax}, got max:{$actualMax}"
            );
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Normalize rules to a flat array of strings.
     *
     * Rules can be declared as:
     * - A pipe-delimited string: 'nullable|string|max:60'
     * - An array: ['nullable', 'string', 'max:60']
     *
     * @return string[]
     */
    private function normalizeRules(array|string $rules): array
    {
        if (\is_string($rules)) {
            return explode('|', $rules);
        }

        return array_values(array_map('strval', $rules));
    }

    /**
     * Generate a random alphanumeric string of the specified length.
     */
    private function randomString(int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($k = 0; $k < $length; ++$k) {
            $str .= $chars[random_int(0, mb_strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * Create a Request instance without calling the constructor.
     *
     * Since Hyperf Request classes require the container/HTTP context in their
     * constructor, we use reflection to instantiate without constructor and
     * directly call the rules() method which has no dependencies.
     *
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    private function createRequestWithoutConstructor(string $className): object
    {
        $reflection = new \ReflectionClass($className);
        return $reflection->newInstanceWithoutConstructor();
    }
}
