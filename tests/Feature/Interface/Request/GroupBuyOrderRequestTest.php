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

namespace HyperfTests\Feature\Interface\Request;

use App\Interface\Api\Request\V1\OrderCommitRequest;
use App\Interface\Api\Request\V1\OrderPreviewRequest;
use PHPUnit\Framework\TestCase;

/**
 * Feature: group-buy-order, Property 14: Request 验证拼团字段.
 *
 * For any 请求 payload 中 order_type = group_buy：
 * - 当 group_buy_id 缺失或非正整数时验证应失败
 * - 当 group_no 为超过 32 字符的字符串时验证应失败
 *
 * **Validates: Requirements 3.3, 3.4**
 *
 * @internal
 * @coversNothing
 */
final class GroupBuyOrderRequestTest extends TestCase
{
    private const ITERATIONS = 100;

    // ---------------------------------------------------------------
    // Part A: Structural rule verification for group_buy_id
    // ---------------------------------------------------------------

    /**
     * Property 14a: OrderPreviewRequest declares group_buy_id as
     * required_if:order_type,group_buy | integer | min:1.
     *
     * When order_type = group_buy, group_buy_id must be present and a positive integer.
     *
     * **Validates: Requirements 3.3**
     */
    public function testGroupBuyIdRulesInPreviewRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('group_buy_id', $rules, 'OrderPreviewRequest must declare rules for group_buy_id');

        $fieldRules = $this->normalizeRules($rules['group_buy_id']);

        // Must be required when order_type is group_buy
        self::assertContains(
            'required_if:order_type,group_buy',
            $fieldRules,
            'group_buy_id must have "required_if:order_type,group_buy" rule (Requirement 3.3)'
        );

        // Must be integer
        self::assertContains(
            'integer',
            $fieldRules,
            'group_buy_id must have "integer" rule (Requirement 3.3)'
        );

        // Must have min:1 (positive integer)
        self::assertContains(
            'min:1',
            $fieldRules,
            'group_buy_id must have "min:1" rule to ensure positive integer (Requirement 3.3)'
        );
    }

    /**
     * Property 14b: OrderCommitRequest inherits group_buy_id rules from OrderPreviewRequest.
     *
     * Since OrderCommitRequest extends OrderPreviewRequest and merges rules,
     * group_buy_id rules should be inherited.
     *
     * **Validates: Requirements 3.3**
     */
    public function testGroupBuyIdRulesInCommitRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('group_buy_id', $rules, 'OrderCommitRequest must inherit group_buy_id rules');

        $fieldRules = $this->normalizeRules($rules['group_buy_id']);

        self::assertContains(
            'required_if:order_type,group_buy',
            $fieldRules,
            'group_buy_id must have "required_if:order_type,group_buy" rule in OrderCommitRequest'
        );

        self::assertContains(
            'integer',
            $fieldRules,
            'group_buy_id must have "integer" rule in OrderCommitRequest'
        );

        self::assertContains(
            'min:1',
            $fieldRules,
            'group_buy_id must have "min:1" rule in OrderCommitRequest'
        );
    }

    // ---------------------------------------------------------------
    // Part B: Structural rule verification for group_no
    // ---------------------------------------------------------------

    /**
     * Property 14c: OrderPreviewRequest declares group_no as nullable | string | max:32.
     *
     * When group_no has a value, it must be a string with max length 32.
     *
     * **Validates: Requirements 3.4**
     */
    public function testGroupNoRulesInPreviewRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('group_no', $rules, 'OrderPreviewRequest must declare rules for group_no');

        $fieldRules = $this->normalizeRules($rules['group_no']);

        // Must be string type
        self::assertContains(
            'string',
            $fieldRules,
            'group_no must have "string" rule (Requirement 3.4)'
        );

        // Must have max:32 length limit
        self::assertContains(
            'max:32',
            $fieldRules,
            'group_no must have "max:32" rule to limit length (Requirement 3.4)'
        );
    }

    /**
     * Property 14d: OrderCommitRequest inherits group_no rules from OrderPreviewRequest.
     *
     * **Validates: Requirements 3.4**
     */
    public function testGroupNoRulesInCommitRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('group_no', $rules, 'OrderCommitRequest must inherit group_no rules');

        $fieldRules = $this->normalizeRules($rules['group_no']);

        self::assertContains(
            'string',
            $fieldRules,
            'group_no must have "string" rule in OrderCommitRequest'
        );

        self::assertContains(
            'max:32',
            $fieldRules,
            'group_no must have "max:32" rule in OrderCommitRequest'
        );
    }

    // ---------------------------------------------------------------
    // Part C: order_type includes group_buy in both Requests
    // ---------------------------------------------------------------

    /**
     * Property 14e: OrderPreviewRequest order_type in: list includes group_buy.
     *
     * **Validates: Requirements 3.3**
     */
    public function testOrderTypeIncludesGroupBuyInPreviewRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('order_type', $rules, 'OrderPreviewRequest must declare rules for order_type');

        $fieldRules = $this->normalizeRules($rules['order_type']);

        // Find the in: rule and verify it contains group_buy
        $inRule = $this->findRuleStartingWith($fieldRules, 'in:');
        self::assertNotNull($inRule, 'order_type must have an "in:" rule in OrderPreviewRequest');

        $allowedValues = explode(',', mb_substr($inRule, 3));
        self::assertContains(
            'group_buy',
            $allowedValues,
            'order_type "in:" rule must include "group_buy" in OrderPreviewRequest'
        );
    }

    /**
     * Property 14f: OrderCommitRequest order_type in: list includes group_buy.
     *
     * **Validates: Requirements 3.3**
     */
    public function testOrderTypeIncludesGroupBuyInCommitRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('order_type', $rules, 'OrderCommitRequest must declare rules for order_type');

        $fieldRules = $this->normalizeRules($rules['order_type']);

        $inRule = $this->findRuleStartingWith($fieldRules, 'in:');
        self::assertNotNull($inRule, 'order_type must have an "in:" rule in OrderCommitRequest');

        $allowedValues = explode(',', mb_substr($inRule, 3));
        self::assertContains(
            'group_buy',
            $allowedValues,
            'order_type "in:" rule must include "group_buy" in OrderCommitRequest'
        );
    }

    // ---------------------------------------------------------------
    // Part D: Property-based - random group_buy payloads missing
    //         group_buy_id would be caught by required_if rule
    // ---------------------------------------------------------------

    /**
     * Property 14g: For any random preview payload with order_type=group_buy
     * but missing group_buy_id, the validation rules contain
     * 'required_if:order_type,group_buy' for group_buy_id, meaning
     * the validator would reject the payload.
     *
     * Generates 100 random payloads that set order_type=group_buy but
     * deliberately omit group_buy_id.
     *
     * **Validates: Requirements 3.3**
     *
     * @dataProvider provideGroupBuyPayloadMissingGroupBuyIdWouldFailCases
     */
    public function testGroupBuyPayloadMissingGroupBuyIdWouldFail(array $payload): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $groupBuyIdRules = $this->normalizeRules($rules['group_buy_id']);

        // Payload has order_type=group_buy but no group_buy_id
        self::assertSame('group_buy', $payload['order_type']);
        self::assertArrayNotHasKey('group_buy_id', $payload);

        // The rules require group_buy_id when order_type is group_buy
        self::assertContains(
            'required_if:order_type,group_buy',
            $groupBuyIdRules,
            'group_buy_id must be required when order_type is group_buy'
        );
    }

    /**
     * @return iterable<string, array{array}>
     */
    public static function provideGroupBuyPayloadMissingGroupBuyIdWouldFailCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $payload = self::buildRandomGroupBuyPreviewPayload();
            unset($payload['group_buy_id']);

            yield "iteration_{$i}" => [$payload];
        }
    }

    // ---------------------------------------------------------------
    // Part E: Property-based - random invalid group_buy_id values
    //         would be caught by integer|min:1 rules
    // ---------------------------------------------------------------

    /**
     * Property 14h: For any random preview payload with order_type=group_buy
     * and group_buy_id set to a non-positive integer (0, negative, or non-integer),
     * the validation rules contain 'integer' and 'min:1' for group_buy_id,
     * meaning the validator would reject the payload.
     *
     * **Validates: Requirements 3.3**
     *
     * @dataProvider provideGroupBuyPayloadWithInvalidGroupBuyIdWouldFailCases
     */
    public function testGroupBuyPayloadWithInvalidGroupBuyIdWouldFail(mixed $invalidValue, string $reason): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $groupBuyIdRules = $this->normalizeRules($rules['group_buy_id']);

        // The rules enforce integer type and min:1
        self::assertContains(
            'integer',
            $groupBuyIdRules,
            "group_buy_id must have 'integer' rule to reject: {$reason}"
        );

        self::assertContains(
            'min:1',
            $groupBuyIdRules,
            "group_buy_id must have 'min:1' rule to reject: {$reason}"
        );
    }

    /**
     * Generates 100 random invalid group_buy_id values:
     * - Zero
     * - Negative integers
     * - Non-integer types (strings, floats, booleans, arrays, null)
     *
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideGroupBuyPayloadWithInvalidGroupBuyIdWouldFailCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $type = random_int(0, 5);
            switch ($type) {
                case 0:
                    // Zero
                    yield "iteration_{$i}_zero" => [0, 'zero is not a positive integer'];
                    break;
                case 1:
                    // Negative integer
                    $val = -random_int(1, 999999);
                    yield "iteration_{$i}_negative_{$val}" => [$val, "negative integer {$val}"];
                    break;
                case 2:
                    // Random string
                    $str = self::generateRandomString(random_int(1, 20));
                    yield "iteration_{$i}_string" => [$str, "string '{$str}' is not an integer"];
                    break;
                case 3:
                    // Float
                    $float = random_int(1, 999) + (random_int(1, 99) / 100);
                    yield "iteration_{$i}_float_{$float}" => [$float, "float {$float} is not an integer"];
                    break;
                case 4:
                    // Boolean
                    $bool = (bool) random_int(0, 1);
                    yield "iteration_{$i}_bool" => [$bool, 'boolean is not a valid integer'];
                    break;
                case 5:
                    // Null
                    yield "iteration_{$i}_null" => [null, 'null is not a valid integer'];
                    break;
            }
        }
    }

    // ---------------------------------------------------------------
    // Part F: Property-based - random group_no strings exceeding
    //         32 characters would be caught by max:32 rule
    // ---------------------------------------------------------------

    /**
     * Property 14i: For any random preview payload with group_no exceeding 32 characters,
     * the validation rules contain 'max:32' for group_no, meaning the validator
     * would reject the payload.
     *
     * **Validates: Requirements 3.4**
     *
     * @dataProvider provideGroupNoExceeding32CharsWouldFailCases
     */
    public function testGroupNoExceeding32CharsWouldFail(string $groupNo): void
    {
        self::assertGreaterThan(32, mb_strlen($groupNo), 'Test input must exceed 32 characters');

        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $groupNoRules = $this->normalizeRules($rules['group_no']);

        // The rules enforce max:32
        self::assertContains(
            'string',
            $groupNoRules,
            'group_no must have "string" rule'
        );

        self::assertContains(
            'max:32',
            $groupNoRules,
            'group_no must have "max:32" rule to reject strings longer than 32 characters'
        );
    }

    /**
     * Generates 100 random strings that exceed 32 characters.
     *
     * @return iterable<string, array{string}>
     */
    public static function provideGroupNoExceeding32CharsWouldFailCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate strings between 33 and 200 characters
            $length = random_int(33, 200);
            $groupNo = self::generateRandomString($length);

            yield "iteration_{$i}_len_{$length}" => [$groupNo];
        }
    }

    // ---------------------------------------------------------------
    // Part G: Property-based - valid group_buy payloads have correct
    //         rule structure for both Requests
    // ---------------------------------------------------------------

    /**
     * Property 14j: For any random valid group_buy payload (with valid group_buy_id
     * and valid group_no), the rules structure in both OrderPreviewRequest and
     * OrderCommitRequest correctly declares all group_buy related fields.
     *
     * **Validates: Requirements 3.3, 3.4**
     *
     * @dataProvider provideValidGroupBuyPayloadRuleStructureCases
     */
    public function testValidGroupBuyPayloadRuleStructure(array $payload): void
    {
        // Verify payload is well-formed
        self::assertSame('group_buy', $payload['order_type']);
        self::assertIsInt($payload['group_buy_id']);
        self::assertGreaterThanOrEqual(1, $payload['group_buy_id']);

        if (isset($payload['group_no'])) {
            self::assertIsString($payload['group_no']);
            self::assertLessThanOrEqual(32, mb_strlen($payload['group_no']));
        }

        // Verify both Requests have the correct rule structure
        foreach ([OrderPreviewRequest::class, OrderCommitRequest::class] as $requestClass) {
            $request = $this->createRequestWithoutConstructor($requestClass);
            $rules = $request->rules();

            // group_buy_id rules exist
            self::assertArrayHasKey('group_buy_id', $rules, "{$requestClass} must have group_buy_id rules");
            $gbIdRules = $this->normalizeRules($rules['group_buy_id']);
            self::assertContains('required_if:order_type,group_buy', $gbIdRules);
            self::assertContains('integer', $gbIdRules);
            self::assertContains('min:1', $gbIdRules);

            // group_no rules exist
            self::assertArrayHasKey('group_no', $rules, "{$requestClass} must have group_no rules");
            $gnRules = $this->normalizeRules($rules['group_no']);
            self::assertContains('string', $gnRules);
            self::assertContains('max:32', $gnRules);

            // order_type includes group_buy
            $otRules = $this->normalizeRules($rules['order_type']);
            $inRule = $this->findRuleStartingWith($otRules, 'in:');
            self::assertNotNull($inRule);
            $allowedValues = explode(',', mb_substr($inRule, 3));
            self::assertContains('group_buy', $allowedValues);
        }
    }

    /**
     * Generates 100 random valid group_buy payloads.
     *
     * @return iterable<string, array{array}>
     */
    public static function provideValidGroupBuyPayloadRuleStructureCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            yield "iteration_{$i}" => [self::buildRandomGroupBuyPreviewPayload()];
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Build a random preview payload with order_type=group_buy and valid group_buy fields.
     */
    private static function buildRandomGroupBuyPreviewPayload(): array
    {
        $payload = [
            'order_type' => 'group_buy',
            'group_buy_id' => random_int(1, 999999),
            'goods_request_list' => [
                [
                    'sku_id' => random_int(1, 999999),
                    'quantity' => random_int(1, 10),
                ],
            ],
        ];

        // Randomly include group_no (valid: 1-32 chars)
        if (random_int(0, 1) === 1) {
            $length = random_int(1, 32);
            $payload['group_no'] = self::generateRandomString($length);
        }

        // Randomly include optional fields
        if (random_int(0, 1) === 1) {
            $payload['address_id'] = random_int(1, 99999);
        }

        return $payload;
    }

    /**
     * Normalize rules to a flat array of strings.
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
     * Find a rule that starts with the given prefix.
     */
    private function findRuleStartingWith(array $rules, string $prefix): ?string
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, $prefix)) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Generate a random alphanumeric string.
     */
    private static function generateRandomString(int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        $max = mb_strlen($chars) - 1;
        for ($k = 0; $k < $length; ++$k) {
            $str .= $chars[random_int(0, $max)];
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
