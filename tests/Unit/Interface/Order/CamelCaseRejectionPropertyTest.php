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
use ReflectionMethod;

/**
 * Feature: order-checkout-refactor, Property 4: camelCase 字段被拒绝.
 *
 * For any 仅包含 camelCase 字段名（如 goodsRequestList）而不包含对应 snake_case 字段名的请求，
 * Request 验证应失败。
 *
 * Since Hyperf Request validation requires the full container/HTTP context which is complex
 * in unit tests, we verify this property structurally:
 * 1. Neither Request class has a prepareForValidation() method that maps camelCase→snake_case
 * 2. All validation rule keys are strictly snake_case (no camelCase keys)
 * 3. For any randomly generated camelCase field name, it does not appear in the rule keys
 *
 * **Validates: Requirements 5.1, 5.2**
 *
 * @internal
 * @coversNothing
 */
final class CamelCaseRejectionPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    /**
     * Known camelCase equivalents of the actual snake_case fields used in the Request classes.
     * These are the exact camelCase names that the old prepareForValidation() used to map.
     */
    private const KNOWN_CAMEL_CASE_FIELDS = [
        'goodsRequestList',
        'orderType',
        'addressId',
        'userAddress',
        'couponList',
        'storeInfoList',
        'totalAmount',
        'userName',
        'invoiceRequest',
        'buyerRemark',
        'payMethod',
    ];

    // ---------------------------------------------------------------
    // Part A: No prepareForValidation with camelCase mapping
    // ---------------------------------------------------------------

    /**
     * Property 4a: OrderPreviewRequest does NOT have a prepareForValidation() method.
     *
     * If prepareForValidation() existed with camelCase→snake_case mapping, the Request
     * would silently accept camelCase fields. Its absence ensures camelCase fields are rejected.
     */
    public function testOrderPreviewRequestHasNoPrepareForValidation(): void
    {
        $reflection = new \ReflectionClass(OrderPreviewRequest::class);

        // The method should not be declared in OrderPreviewRequest itself
        if ($reflection->hasMethod('prepareForValidation')) {
            $method = $reflection->getMethod('prepareForValidation');
            // If it exists, it must NOT be declared in OrderPreviewRequest (could be inherited)
            self::assertNotSame(
                OrderPreviewRequest::class,
                $method->getDeclaringClass()->getName(),
                'OrderPreviewRequest should not declare prepareForValidation() - camelCase mapping must be removed (Requirement 5.1)'
            );
        } else {
            // Method doesn't exist at all - this is the expected state
            self::assertTrue(true, 'OrderPreviewRequest correctly has no prepareForValidation()');
        }
    }

    /**
     * Property 4b: OrderCommitRequest does NOT have a prepareForValidation() method.
     */
    public function testOrderCommitRequestHasNoPrepareForValidation(): void
    {
        $reflection = new \ReflectionClass(OrderCommitRequest::class);

        if ($reflection->hasMethod('prepareForValidation')) {
            $method = $reflection->getMethod('prepareForValidation');
            self::assertNotSame(
                OrderCommitRequest::class,
                $method->getDeclaringClass()->getName(),
                'OrderCommitRequest should not declare prepareForValidation() - camelCase mapping must be removed (Requirement 5.2)'
            );
        } else {
            self::assertTrue(true, 'OrderCommitRequest correctly has no prepareForValidation()');
        }
    }

    // ---------------------------------------------------------------
    // Part B: All rule keys are snake_case
    // ---------------------------------------------------------------

    /**
     * Property 4c: All OrderPreviewRequest rule keys are snake_case.
     *
     * For every key in the rules() array, the top-level field name (before any dot)
     * must be snake_case. No camelCase field names are accepted.
     */
    public function testOrderPreviewRequestRuleKeysAreSnakeCase(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();

        self::assertNotEmpty($rules, 'OrderPreviewRequest rules should not be empty');

        foreach ($rules as $key => $ruleSet) {
            $topLevelField = explode('.', $key)[0];
            self::assertMatchesRegularExpression(
                '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/',
                $topLevelField,
                "Rule key '{$key}' has a non-snake_case top-level field '{$topLevelField}'"
            );

            // Also verify nested field segments (after wildcard *) are snake_case
            $segments = explode('.', $key);
            foreach ($segments as $segment) {
                if ($segment === '*') {
                    continue;
                }
                self::assertMatchesRegularExpression(
                    '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/',
                    $segment,
                    "Rule key '{$key}' contains non-snake_case segment '{$segment}'"
                );
            }
        }
    }

    /**
     * Property 4d: All OrderCommitRequest rule keys are snake_case.
     */
    public function testOrderCommitRequestRuleKeysAreSnakeCase(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();

        self::assertNotEmpty($rules, 'OrderCommitRequest rules should not be empty');

        foreach ($rules as $key => $ruleSet) {
            $topLevelField = explode('.', $key)[0];
            self::assertMatchesRegularExpression(
                '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/',
                $topLevelField,
                "Rule key '{$key}' has a non-snake_case top-level field '{$topLevelField}'"
            );

            $segments = explode('.', $key);
            foreach ($segments as $segment) {
                if ($segment === '*') {
                    continue;
                }
                self::assertMatchesRegularExpression(
                    '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/',
                    $segment,
                    "Rule key '{$key}' contains non-snake_case segment '{$segment}'"
                );
            }
        }
    }

    // ---------------------------------------------------------------
    // Part C: Known camelCase fields are NOT in rule keys
    // ---------------------------------------------------------------

    /**
     * Property 4e: Known camelCase field names do not appear in OrderPreviewRequest rules.
     */
    public function testKnownCamelCaseFieldsNotInPreviewRules(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $ruleKeys = array_keys($rules);

        // Extract all top-level field names from rule keys
        $topLevelFields = array_unique(array_map(
            static fn (string $key) => explode('.', $key)[0],
            $ruleKeys
        ));

        foreach (self::KNOWN_CAMEL_CASE_FIELDS as $camelField) {
            self::assertNotContains(
                $camelField,
                $topLevelFields,
                "OrderPreviewRequest rules should not contain camelCase field '{$camelField}'"
            );
        }
    }

    /**
     * Property 4f: Known camelCase field names do not appear in OrderCommitRequest rules.
     */
    public function testKnownCamelCaseFieldsNotInCommitRules(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();
        $ruleKeys = array_keys($rules);

        $topLevelFields = array_unique(array_map(
            static fn (string $key) => explode('.', $key)[0],
            $ruleKeys
        ));

        foreach (self::KNOWN_CAMEL_CASE_FIELDS as $camelField) {
            self::assertNotContains(
                $camelField,
                $topLevelFields,
                "OrderCommitRequest rules should not contain camelCase field '{$camelField}'"
            );
        }
    }

    // ---------------------------------------------------------------
    // Part D: Property-based - random camelCase fields not in rules
    // ---------------------------------------------------------------

    /**
     * Property 4g: For any randomly generated camelCase field name, it does not
     * appear as a rule key in OrderPreviewRequest.
     *
     * This is the property-based aspect: we generate 100 random camelCase strings
     * and verify none of them match any rule key. Since all rule keys are snake_case,
     * any camelCase field would be unrecognized and thus rejected by validation.
     */
    public function testRandomCamelCaseFieldsNotInPreviewRules(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderPreviewRequest::class);
        $rules = $request->rules();
        $ruleKeys = array_keys($rules);

        // Also collect all segments for thorough checking
        $allSegments = [];
        foreach ($ruleKeys as $key) {
            foreach (explode('.', $key) as $segment) {
                if ($segment !== '*') {
                    $allSegments[] = $segment;
                }
            }
        }
        $allSegments = array_unique($allSegments);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $camelCaseField = $this->randomCamelCaseFieldName();

            // The camelCase field should not be a top-level rule key
            self::assertNotContains(
                $camelCaseField,
                $ruleKeys,
                "Iteration {$i}: Random camelCase field '{$camelCaseField}' should not be a rule key in OrderPreviewRequest"
            );

            // The camelCase field should not appear as any segment in rule keys
            self::assertNotContains(
                $camelCaseField,
                $allSegments,
                "Iteration {$i}: Random camelCase field '{$camelCaseField}' should not appear as a segment in OrderPreviewRequest rule keys"
            );
        }
    }

    /**
     * Property 4h: For any randomly generated camelCase field name, it does not
     * appear as a rule key in OrderCommitRequest.
     */
    public function testRandomCamelCaseFieldsNotInCommitRules(): void
    {
        $request = $this->createRequestWithoutConstructor(OrderCommitRequest::class);
        $rules = $request->rules();
        $ruleKeys = array_keys($rules);

        $allSegments = [];
        foreach ($ruleKeys as $key) {
            foreach (explode('.', $key) as $segment) {
                if ($segment !== '*') {
                    $allSegments[] = $segment;
                }
            }
        }
        $allSegments = array_unique($allSegments);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $camelCaseField = $this->randomCamelCaseFieldName();

            self::assertNotContains(
                $camelCaseField,
                $ruleKeys,
                "Iteration {$i}: Random camelCase field '{$camelCaseField}' should not be a rule key in OrderCommitRequest"
            );

            self::assertNotContains(
                $camelCaseField,
                $allSegments,
                "Iteration {$i}: Random camelCase field '{$camelCaseField}' should not appear as a segment in OrderCommitRequest rule keys"
            );
        }
    }

    // ---------------------------------------------------------------
    // Part E: Structural verification - no camelCase mapping in source
    // ---------------------------------------------------------------

    /**
     * Property 4i: If prepareForValidation exists in any parent class, verify it
     * does NOT contain camelCase→snake_case field mapping patterns.
     *
     * This uses reflection to inspect the method body for common mapping patterns
     * like 'goodsRequestList', 'goods_request_list' => ... camelCase references.
     */
    public function testNoCamelCaseMappingInRequestClassHierarchy(): void
    {
        $classes = [OrderPreviewRequest::class, OrderCommitRequest::class];

        foreach ($classes as $className) {
            $reflection = new \ReflectionClass($className);

            // Check if the class itself declares prepareForValidation
            if (! $reflection->hasMethod('prepareForValidation')) {
                continue;
            }

            $method = $reflection->getMethod('prepareForValidation');

            // Only check if declared in our Request classes (not framework parent)
            $declaringClass = $method->getDeclaringClass()->getName();
            if ($declaringClass !== OrderPreviewRequest::class && $declaringClass !== OrderCommitRequest::class) {
                continue;
            }

            // Read the method source to check for camelCase mapping patterns
            $source = $this->getMethodSource($method);

            foreach (self::KNOWN_CAMEL_CASE_FIELDS as $camelField) {
                self::assertStringNotContainsString(
                    $camelField,
                    $source,
                    "{$declaringClass}::prepareForValidation() should not reference camelCase field '{$camelField}'"
                );
            }
        }

        // If we get here without any assertions from the loop, both classes are clean
        self::assertTrue(true, 'No camelCase mapping found in Request class hierarchy');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Generate a random camelCase field name.
     *
     * Produces strings like "fooBar", "myFieldName", "someValue123" etc.
     * These are guaranteed to contain at least one uppercase letter after the first char,
     * making them definitively camelCase (not snake_case).
     */
    private function randomCamelCaseFieldName(): string
    {
        // Strategy: build a camelCase name from 2-4 word segments
        $wordCount = random_int(2, 4);
        $words = [];

        for ($w = 0; $w < $wordCount; ++$w) {
            $wordLen = random_int(2, 8);
            $word = '';
            for ($c = 0; $c < $wordLen; ++$c) {
                $word .= \chr(random_int(\ord('a'), \ord('z')));
            }
            $words[] = $word;
        }

        // First word stays lowercase, subsequent words get ucfirst → camelCase
        $result = $words[0];
        for ($w = 1; $w < \count($words); ++$w) {
            $result .= ucfirst($words[$w]);
        }

        return $result;
    }

    /**
     * Read the source code of a ReflectionMethod.
     */
    private function getMethodSource(\ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        if (! $filename || ! file_exists($filename)) {
            return '';
        }

        $lines = file($filename);
        $startLine = $method->getStartLine() - 1; // 0-indexed
        $endLine = $method->getEndLine();

        return implode('', \array_slice($lines, $startLine, $endLine - $startLine));
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
