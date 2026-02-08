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

use App\Interface\Api\Request\V1\OrderCommitRequest;
use App\Interface\Api\Request\V1\OrderPreviewRequest;
use PHPUnit\Framework\TestCase;

/**
 * Feature: order-checkout-refactor, Property 5: 必填字段缺失时验证失败.
 *
 * For any 缺少 goods_request_list 的预览请求，验证应失败。
 * For any 缺少 total_amount 或 order_type 的提交请求，验证应失败。
 *
 * Since Hyperf Request validation requires the full container/HTTP context which is complex
 * in unit tests, we verify this property structurally:
 * 1. Using reflection to instantiate Request classes without constructor
 * 2. Calling rules() to get the validation rules
 * 3. Verifying that required fields have 'required' in their rule set
 * 4. Verifying that 'required' fields do NOT have 'nullable' (which would weaken the requirement)
 * 5. For the property-based aspect: generate random subsets of fields missing the required ones
 *    and verify the rules would catch them
 *
 * **Validates: Requirements 7.1, 7.2, 7.3**
 *
 * @internal
 * @coversNothing
 */
final class RequiredFieldValidationPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    // ---------------------------------------------------------------
    // Part A: goods_request_list has 'required' rule in OrderPreviewRequest
    // ---------------------------------------------------------------

    /**
     * Property 5a: OrderPreviewRequest declares goods_request_list as required|array|min:1.
     *
     * Validates: Requirement 7.1
     */
    public function testGoodsRequestListIsRequiredInPreviewRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('goods_request_list', $rules, 'OrderPreviewRequest must declare rules for goods_request_list');

        $fieldRules = $this->normalizeRules($rules['goods_request_list']);

        self::assertContains('required', $fieldRules, 'goods_request_list must have "required" rule (Requirement 7.1)');
        self::assertContains('array', $fieldRules, 'goods_request_list must have "array" rule (Requirement 7.1)');
        self::assertNotContains('nullable', $fieldRules, 'goods_request_list must NOT have "nullable" - required fields cannot be nullable');

        // Verify min:1 is present
        $hasMin1 = false;
        foreach ($fieldRules as $rule) {
            if ($rule === 'min:1') {
                $hasMin1 = true;
                break;
            }
        }
        self::assertTrue($hasMin1, 'goods_request_list must have "min:1" rule (Requirement 7.1)');
    }

    // ---------------------------------------------------------------
    // Part B: total_amount has 'required' rule in OrderCommitRequest (not nullable)
    // ---------------------------------------------------------------

    /**
     * Property 5b: OrderCommitRequest declares total_amount as required|integer|min:0, not nullable.
     *
     * Validates: Requirement 7.2
     */
    public function testTotalAmountIsRequiredInCommitRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('total_amount', $rules, 'OrderCommitRequest must declare rules for total_amount');

        $fieldRules = $this->normalizeRules($rules['total_amount']);

        self::assertContains('required', $fieldRules, 'total_amount must have "required" rule (Requirement 7.2)');
        self::assertContains('integer', $fieldRules, 'total_amount must have "integer" rule (Requirement 7.2)');
        self::assertNotContains('nullable', $fieldRules, 'total_amount must NOT have "nullable" - required fields cannot be nullable (Requirement 7.2)');

        // Verify min:0 is present
        $hasMin0 = false;
        foreach ($fieldRules as $rule) {
            if ($rule === 'min:0') {
                $hasMin0 = true;
                break;
            }
        }
        self::assertTrue($hasMin0, 'total_amount must have "min:0" rule (Requirement 7.2)');
    }

    // ---------------------------------------------------------------
    // Part C: order_type has 'required' rule in OrderCommitRequest
    // ---------------------------------------------------------------

    /**
     * Property 5c: OrderCommitRequest declares order_type as required|string|in:normal.
     *
     * Validates: Requirement 7.3
     */
    public function testOrderTypeIsRequiredInCommitRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('order_type', $rules, 'OrderCommitRequest must declare rules for order_type');

        $fieldRules = $this->normalizeRules($rules['order_type']);

        self::assertContains('required', $fieldRules, 'order_type must have "required" rule (Requirement 7.3)');
        self::assertContains('string', $fieldRules, 'order_type must have "string" rule (Requirement 7.3)');
        self::assertNotContains('nullable', $fieldRules, 'order_type must NOT have "nullable" - required fields cannot be nullable (Requirement 7.3)');

        // Verify in:normal is present
        $hasInNormal = false;
        foreach ($fieldRules as $rule) {
            if ($rule === 'in:normal') {
                $hasInNormal = true;
                break;
            }
        }
        self::assertTrue($hasInNormal, 'order_type must have "in:normal" rule (Requirement 7.3)');
    }

    // ---------------------------------------------------------------
    // Part D: goods_request_list sub-field rules are required
    // ---------------------------------------------------------------

    /**
     * Property 5d: OrderPreviewRequest declares goods_request_list.*.sku_id and
     * goods_request_list.*.quantity as required.
     *
     * Validates: Requirement 7.1
     */
    public function testGoodsRequestListSubFieldsAreRequired(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        // sku_id
        self::assertArrayHasKey('goods_request_list.*.sku_id', $rules, 'Must declare rules for goods_request_list.*.sku_id');
        $skuRules = $this->normalizeRules($rules['goods_request_list.*.sku_id']);
        self::assertContains('required', $skuRules, 'goods_request_list.*.sku_id must have "required" rule');
        self::assertContains('integer', $skuRules, 'goods_request_list.*.sku_id must have "integer" rule');
        self::assertNotContains('nullable', $skuRules, 'goods_request_list.*.sku_id must NOT have "nullable"');

        // quantity
        self::assertArrayHasKey('goods_request_list.*.quantity', $rules, 'Must declare rules for goods_request_list.*.quantity');
        $qtyRules = $this->normalizeRules($rules['goods_request_list.*.quantity']);
        self::assertContains('required', $qtyRules, 'goods_request_list.*.quantity must have "required" rule');
        self::assertContains('integer', $qtyRules, 'goods_request_list.*.quantity must have "integer" rule');
        self::assertNotContains('nullable', $qtyRules, 'goods_request_list.*.quantity must NOT have "nullable"');
    }

    // ---------------------------------------------------------------
    // Part E: OrderCommitRequest inherits goods_request_list required rule
    // ---------------------------------------------------------------

    /**
     * Property 5e: OrderCommitRequest (which extends OrderPreviewRequest) also has
     * goods_request_list as required.
     *
     * Validates: Requirement 7.1
     */
    public function testGoodsRequestListIsRequiredInCommitRequest(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertArrayHasKey('goods_request_list', $rules, 'OrderCommitRequest must inherit goods_request_list rules');

        $fieldRules = $this->normalizeRules($rules['goods_request_list']);

        self::assertContains('required', $fieldRules, 'goods_request_list must have "required" rule in OrderCommitRequest');
        self::assertNotContains('nullable', $fieldRules, 'goods_request_list must NOT have "nullable" in OrderCommitRequest');
    }

    // ---------------------------------------------------------------
    // Part F: Property-based - random payloads missing required fields
    //         would be caught by the rules
    // ---------------------------------------------------------------

    /**
     * Property 5f: For any random preview payload missing goods_request_list,
     * the validation rules contain 'required' for goods_request_list, meaning
     * the validator would reject the payload.
     *
     * We generate 100 random payloads that deliberately omit goods_request_list
     * and verify the rules would catch this omission.
     *
     * Validates: Requirement 7.1
     */
    public function testRandomPreviewPayloadsMissingGoodsRequestListWouldFail(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $goodsRules = $this->normalizeRules($rules['goods_request_list']);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate a random payload that does NOT include goods_request_list
            $payload = $this->randomPreviewPayloadWithout(['goods_request_list']);

            // The payload must not contain goods_request_list
            self::assertArrayNotHasKey(
                'goods_request_list',
                $payload,
                "Iteration {$i}: Generated payload should not contain goods_request_list"
            );

            // The rules require goods_request_list - so validation would fail
            self::assertContains(
                'required',
                $goodsRules,
                "Iteration {$i}: goods_request_list rules must include 'required' to reject payloads missing this field"
            );

            // Verify 'nullable' is not present (which would allow null/missing)
            self::assertNotContains(
                'nullable',
                $goodsRules,
                "Iteration {$i}: goods_request_list must not be nullable"
            );
        }
    }

    /**
     * Property 5g: For any random commit payload missing total_amount,
     * the validation rules contain 'required' for total_amount, meaning
     * the validator would reject the payload.
     *
     * Validates: Requirement 7.2
     */
    public function testRandomCommitPayloadsMissingTotalAmountWouldFail(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();
        $totalAmountRules = $this->normalizeRules($rules['total_amount']);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate a random commit payload that does NOT include total_amount
            $payload = $this->randomCommitPayloadWithout(['total_amount']);

            self::assertArrayNotHasKey(
                'total_amount',
                $payload,
                "Iteration {$i}: Generated payload should not contain total_amount"
            );

            self::assertContains(
                'required',
                $totalAmountRules,
                "Iteration {$i}: total_amount rules must include 'required' to reject payloads missing this field"
            );

            self::assertNotContains(
                'nullable',
                $totalAmountRules,
                "Iteration {$i}: total_amount must not be nullable (Requirement 7.2)"
            );
        }
    }

    /**
     * Property 5h: For any random commit payload missing order_type,
     * the validation rules contain 'required' for order_type, meaning
     * the validator would reject the payload.
     *
     * Validates: Requirement 7.3
     */
    public function testRandomCommitPayloadsMissingOrderTypeWouldFail(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();
        $orderTypeRules = $this->normalizeRules($rules['order_type']);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate a random commit payload that does NOT include order_type
            $payload = $this->randomCommitPayloadWithout(['order_type']);

            self::assertArrayNotHasKey(
                'order_type',
                $payload,
                "Iteration {$i}: Generated payload should not contain order_type"
            );

            self::assertContains(
                'required',
                $orderTypeRules,
                "Iteration {$i}: order_type rules must include 'required' to reject payloads missing this field"
            );

            self::assertNotContains(
                'nullable',
                $orderTypeRules,
                "Iteration {$i}: order_type must not be nullable (Requirement 7.3)"
            );
        }
    }

    /**
     * Property 5i: For any random commit payload missing BOTH total_amount AND order_type,
     * the validation rules contain 'required' for both fields.
     *
     * Validates: Requirements 7.2, 7.3
     */
    public function testRandomCommitPayloadsMissingBothTotalAmountAndOrderTypeWouldFail(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();
        $totalAmountRules = $this->normalizeRules($rules['total_amount']);
        $orderTypeRules = $this->normalizeRules($rules['order_type']);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Generate a random commit payload missing both fields
            $payload = $this->randomCommitPayloadWithout(['total_amount', 'order_type']);

            self::assertArrayNotHasKey(
                'total_amount',
                $payload,
                "Iteration {$i}: Generated payload should not contain total_amount"
            );
            self::assertArrayNotHasKey(
                'order_type',
                $payload,
                "Iteration {$i}: Generated payload should not contain order_type"
            );

            // Both fields must be required
            self::assertContains('required', $totalAmountRules, "Iteration {$i}: total_amount must be required");
            self::assertContains('required', $orderTypeRules, "Iteration {$i}: order_type must be required");

            // Neither can be nullable
            self::assertNotContains('nullable', $totalAmountRules, "Iteration {$i}: total_amount must not be nullable");
            self::assertNotContains('nullable', $orderTypeRules, "Iteration {$i}: order_type must not be nullable");
        }
    }

    // ---------------------------------------------------------------
    // Part G: Property-based - random subsets of required fields
    //         are all covered by 'required' rules
    // ---------------------------------------------------------------

    /**
     * Property 5j: For any random subset of the required fields in OrderCommitRequest,
     * every field in the subset has 'required' in its rule set and does NOT have 'nullable'.
     *
     * This tests that no required field accidentally has its 'required' rule weakened.
     *
     * Validates: Requirements 7.1, 7.2, 7.3
     */
    public function testRandomSubsetsOfRequiredFieldsAreAllRequired(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        // All fields that MUST be required in OrderCommitRequest
        $requiredFields = [
            'goods_request_list',
            'goods_request_list.*.sku_id',
            'goods_request_list.*.quantity',
            'order_type',
            'total_amount',
        ];

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            // Pick a random non-empty subset of required fields
            $subsetSize = random_int(1, \count($requiredFields));
            $shuffled = $requiredFields;
            shuffle($shuffled);
            $subset = \array_slice($shuffled, 0, $subsetSize);

            foreach ($subset as $field) {
                self::assertArrayHasKey(
                    $field,
                    $rules,
                    "Iteration {$i}: OrderCommitRequest must declare rules for '{$field}'"
                );

                $fieldRules = $this->normalizeRules($rules[$field]);

                self::assertContains(
                    'required',
                    $fieldRules,
                    "Iteration {$i}: Field '{$field}' must have 'required' rule"
                );

                self::assertNotContains(
                    'nullable',
                    $fieldRules,
                    "Iteration {$i}: Field '{$field}' must NOT have 'nullable' (weakens required)"
                );
            }
        }
    }

    // ---------------------------------------------------------------
    // Random payload generators
    // ---------------------------------------------------------------

    /**
     * Generate a random preview-like payload that deliberately excludes specified fields.
     *
     * @param string[] $excludeFields Fields to exclude from the payload
     */
    private function randomPreviewPayloadWithout(array $excludeFields): array
    {
        $payload = [];

        // Possibly include goods_request_list
        if (! \in_array('goods_request_list', $excludeFields, true)) {
            $count = random_int(1, 5);
            $list = [];
            for ($j = 0; $j < $count; ++$j) {
                $list[] = [
                    'sku_id' => random_int(1, 999999),
                    'quantity' => random_int(1, 999),
                ];
            }
            $payload['goods_request_list'] = $list;
        }

        // Possibly include order_type
        if (! \in_array('order_type', $excludeFields, true) && random_int(0, 1) === 1) {
            $payload['order_type'] = 'normal';
        }

        // Possibly include address_id
        if (random_int(0, 1) === 1) {
            $payload['address_id'] = random_int(1, 99999);
        }

        // Possibly include user_address
        if (random_int(0, 1) === 1) {
            $payload['user_address'] = [
                'name' => $this->randomString(random_int(1, 60)),
                'phone' => $this->randomString(random_int(1, 20)),
                'province' => $this->randomString(random_int(1, 30)),
                'city' => $this->randomString(random_int(1, 30)),
                'district' => $this->randomString(random_int(1, 30)),
                'detail' => $this->randomString(random_int(1, 200)),
            ];
        }

        // Possibly include coupon_id
        if (random_int(0, 1) === 1) {
            $payload['coupon_id'] = random_int(1, 99999);
        }

        // Possibly include store_info_list
        if (random_int(0, 1) === 1) {
            $payload['store_info_list'] = [['remark' => $this->randomString(random_int(0, 200))]];
        }

        // Add some random extra fields to simulate noisy payloads
        $extraFieldCount = random_int(0, 3);
        for ($e = 0; $e < $extraFieldCount; ++$e) {
            $extraKey = 'extra_field_' . random_int(1, 1000);
            $payload[$extraKey] = $this->randomString(random_int(1, 50));
        }

        return $payload;
    }

    /**
     * Generate a random commit-like payload that deliberately excludes specified fields.
     *
     * @param string[] $excludeFields Fields to exclude from the payload
     */
    private function randomCommitPayloadWithout(array $excludeFields): array
    {
        $payload = $this->randomPreviewPayloadWithout($excludeFields);

        // Possibly include total_amount
        if (! \in_array('total_amount', $excludeFields, true)) {
            $payload['total_amount'] = random_int(0, 9999999);
        }

        // Possibly include order_type (required for commit, override preview's nullable)
        if (! \in_array('order_type', $excludeFields, true)) {
            $payload['order_type'] = 'normal';
        }

        // Possibly include user_name
        if (random_int(0, 1) === 1) {
            $payload['user_name'] = $this->randomString(random_int(1, 60));
        }

        // Possibly include invoice_request
        if (random_int(0, 1) === 1) {
            $payload['invoice_request'] = ['type' => 'personal'];
        }

        return $payload;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Normalize rules to a flat array of strings.
     *
     * Rules can be declared as:
     * - A pipe-delimited string: 'required|array|min:1'
     * - An array: ['required', 'array', 'min:1']
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
     * Generate a random alphanumeric string.
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
