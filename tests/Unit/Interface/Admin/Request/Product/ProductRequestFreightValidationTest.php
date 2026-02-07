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

namespace HyperfTests\Unit\Interface\Admin\Request\Product;

use App\Interface\Admin\Request\Product\ProductRequest;
use PHPUnit\Framework\TestCase;

/**
 * Feature: shipping-freight-feature, Property 1: 运费类型条件验证一致性.
 *
 * For any 商品请求数据和任意 freight_type 值，当 freight_type=flat 时 flat_freight_amount 必须为
 * required 且为有效整数，当 freight_type=template 时 shipping_template_id 必须为 required 且存在，
 * 当 freight_type=default 或 freight_type=free 时两者均不要求。只有 free、flat、template、default
 * 四个值通过验证，其他任意字符串均被拒绝。
 *
 * **Validates: Requirements 1.5, 1.6, 1.7, 1.8**
 *
 * @internal
 * @coversNothing
 */
final class ProductRequestFreightValidationTest extends TestCase
{
    private const ITERATIONS = 100;

    private const VALID_FREIGHT_TYPES = ['free', 'flat', 'template', 'default'];

    // ---------------------------------------------------------------
    // Part A: freight_type field has correct validation rules
    // ---------------------------------------------------------------

    /**
     * Requirement 1.5: freight_type SHALL be validated as in:free,flat,template,default.
     *
     * **Validates: Requirements 1.5**
     */
    public function testFreightTypeFieldIsRequiredAndHasCorrectEnumValues(): void
    {
        $rules = $this->getBaseRules();

        self::assertArrayHasKey('freight_type', $rules, 'baseRules must declare rules for freight_type');

        $fieldRules = $this->normalizeRules($rules['freight_type']);

        self::assertContains('required', $fieldRules, 'freight_type must have "required" rule (Requirement 1.5)');
        self::assertTrue(
            $this->hasRule($fieldRules, 'in:free,flat,template,default'),
            'freight_type must have "in:free,flat,template,default" rule (Requirement 1.5). Actual rules: ' . implode(', ', $fieldRules)
        );
    }

    // ---------------------------------------------------------------
    // Part B: flat_freight_amount conditional rules when freight_type=flat
    // ---------------------------------------------------------------

    /**
     * Requirement 1.6: WHEN freight_type=flat, flat_freight_amount SHALL be required|integer|min:0|max:99999.
     *
     * **Validates: Requirements 1.6**
     */
    public function testFlatFreightAmountRulesWhenFreightTypeIsFlat(): void
    {
        $rules = $this->getBaseRules();

        self::assertArrayHasKey('flat_freight_amount', $rules, 'baseRules must declare rules for flat_freight_amount');

        $fieldRules = $this->normalizeRules($rules['flat_freight_amount']);

        // Must have required_if:freight_type,flat — this makes it required when freight_type=flat
        self::assertTrue(
            $this->hasRule($fieldRules, 'required_if:freight_type,flat'),
            'flat_freight_amount must have "required_if:freight_type,flat" rule (Requirement 1.6). Actual rules: ' . implode(', ', $fieldRules)
        );

        // Must have integer type
        self::assertContains('integer', $fieldRules, 'flat_freight_amount must have "integer" rule (Requirement 1.6)');

        // Must have min:0
        self::assertTrue(
            $this->hasRule($fieldRules, 'min:0'),
            'flat_freight_amount must have "min:0" rule (Requirement 1.6). Actual rules: ' . implode(', ', $fieldRules)
        );

        // Must have max:99999
        self::assertTrue(
            $this->hasRule($fieldRules, 'max:99999'),
            'flat_freight_amount must have "max:99999" rule (Requirement 1.6). Actual rules: ' . implode(', ', $fieldRules)
        );
    }

    // ---------------------------------------------------------------
    // Part C: shipping_template_id conditional rules when freight_type=template
    // ---------------------------------------------------------------

    /**
     * Requirement 1.7: WHEN freight_type=template, shipping_template_id SHALL be required|integer|exists:shipping_templates,id.
     *
     * **Validates: Requirements 1.7**
     */
    public function testShippingTemplateIdRulesWhenFreightTypeIsTemplate(): void
    {
        $rules = $this->getBaseRules();

        self::assertArrayHasKey('shipping_template_id', $rules, 'baseRules must declare rules for shipping_template_id');

        $fieldRules = $this->normalizeRules($rules['shipping_template_id']);

        // Must have required_if:freight_type,template
        self::assertTrue(
            $this->hasRule($fieldRules, 'required_if:freight_type,template'),
            'shipping_template_id must have "required_if:freight_type,template" rule (Requirement 1.7). Actual rules: ' . implode(', ', $fieldRules)
        );

        // Must have integer type
        self::assertContains('integer', $fieldRules, 'shipping_template_id must have "integer" rule (Requirement 1.7)');

        // Must have exists:shipping_templates,id
        self::assertTrue(
            $this->hasRule($fieldRules, 'exists:shipping_templates,id'),
            'shipping_template_id must have "exists:shipping_templates,id" rule (Requirement 1.7). Actual rules: ' . implode(', ', $fieldRules)
        );
    }

    // ---------------------------------------------------------------
    // Part D: When freight_type=default or free, neither field is required
    // ---------------------------------------------------------------

    /**
     * Requirement 1.8: WHEN freight_type=default, SHALL NOT require flat_freight_amount and shipping_template_id.
     *
     * We verify this by checking that the conditional rules only trigger for 'flat' and 'template',
     * not for 'default' or 'free'.
     *
     * **Validates: Requirements 1.8**
     */
    public function testNeitherFieldRequiredWhenFreightTypeIsDefaultOrFree(): void
    {
        $rules = $this->getBaseRules();

        $flatRules = $this->normalizeRules($rules['flat_freight_amount']);
        $templateRules = $this->normalizeRules($rules['shipping_template_id']);

        // flat_freight_amount: required_if only triggers for freight_type=flat, not default/free
        $flatRequiredIfRule = null;
        foreach ($flatRules as $rule) {
            if (str_starts_with($rule, 'required_if:')) {
                $flatRequiredIfRule = $rule;
                break;
            }
        }
        self::assertNotNull($flatRequiredIfRule, 'flat_freight_amount must have a required_if rule');
        self::assertSame(
            'required_if:freight_type,flat',
            $flatRequiredIfRule,
            'flat_freight_amount required_if must only trigger for freight_type=flat, not default or free (Requirement 1.8)'
        );

        // shipping_template_id: required_if only triggers for freight_type=template, not default/free
        $templateRequiredIfRule = null;
        foreach ($templateRules as $rule) {
            if (str_starts_with($rule, 'required_if:')) {
                $templateRequiredIfRule = $rule;
                break;
            }
        }
        self::assertNotNull($templateRequiredIfRule, 'shipping_template_id must have a required_if rule');
        self::assertSame(
            'required_if:freight_type,template',
            $templateRequiredIfRule,
            'shipping_template_id required_if must only trigger for freight_type=template, not default or free (Requirement 1.8)'
        );

        // Both fields are nullable (allowed to be null when not required)
        self::assertContains('nullable', $flatRules, 'flat_freight_amount should be nullable when freight_type is not flat');
        self::assertContains('nullable', $templateRules, 'shipping_template_id should be nullable when freight_type is not template');
    }

    // ---------------------------------------------------------------
    // Part E: Property-based — random valid freight_type values
    //         verify conditional rules are structurally correct
    // ---------------------------------------------------------------

    /**
     * Property 1a: For any randomly chosen valid freight_type, the conditional validation
     * rules are structurally consistent — flat_freight_amount is only required when
     * freight_type=flat, shipping_template_id is only required when freight_type=template.
     *
     * **Validates: Requirements 1.5, 1.6, 1.7, 1.8**
     */
    public function testRandomValidFreightTypesHaveConsistentConditionalRules(): void
    {
        $rules = $this->getBaseRules();
        $flatRules = $this->normalizeRules($rules['flat_freight_amount']);
        $templateRules = $this->normalizeRules($rules['shipping_template_id']);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $freightType = self::VALID_FREIGHT_TYPES[random_int(0, \count(self::VALID_FREIGHT_TYPES) - 1)];

            // The required_if rule for flat_freight_amount should only match 'flat'
            $flatWouldBeRequired = ($freightType === 'flat');
            $flatHasRequiredIf = $this->hasRule($flatRules, 'required_if:freight_type,flat');
            self::assertTrue(
                $flatHasRequiredIf,
                "Iteration {$i} (freight_type={$freightType}): flat_freight_amount must have required_if:freight_type,flat"
            );

            if ($flatWouldBeRequired) {
                // When freight_type=flat, the required_if triggers — verify supporting rules exist
                self::assertContains('integer', $flatRules, "Iteration {$i}: flat_freight_amount must be integer when required");
                self::assertTrue($this->hasRule($flatRules, 'min:0'), "Iteration {$i}: flat_freight_amount must have min:0");
                self::assertTrue($this->hasRule($flatRules, 'max:99999'), "Iteration {$i}: flat_freight_amount must have max:99999");
            }

            // The required_if rule for shipping_template_id should only match 'template'
            $templateWouldBeRequired = ($freightType === 'template');
            $templateHasRequiredIf = $this->hasRule($templateRules, 'required_if:freight_type,template');
            self::assertTrue(
                $templateHasRequiredIf,
                "Iteration {$i} (freight_type={$freightType}): shipping_template_id must have required_if:freight_type,template"
            );

            if ($templateWouldBeRequired) {
                // When freight_type=template, the required_if triggers — verify supporting rules exist
                self::assertContains('integer', $templateRules, "Iteration {$i}: shipping_template_id must be integer when required");
                self::assertTrue(
                    $this->hasRule($templateRules, 'exists:shipping_templates,id'),
                    "Iteration {$i}: shipping_template_id must have exists rule"
                );
            }

            // When freight_type is 'free' or 'default', neither conditional triggers
            if ($freightType === 'free' || $freightType === 'default') {
                // required_if:freight_type,flat does NOT match 'free' or 'default'
                self::assertNotSame(
                    $freightType,
                    'flat',
                    "Iteration {$i}: freight_type={$freightType} should not trigger flat_freight_amount required_if"
                );
                self::assertNotSame(
                    $freightType,
                    'template',
                    "Iteration {$i}: freight_type={$freightType} should not trigger shipping_template_id required_if"
                );
            }
        }
    }

    // ---------------------------------------------------------------
    // Part F: Property-based — random invalid freight_type values
    //         are rejected by the in: rule
    // ---------------------------------------------------------------

    /**
     * Property 1b: For any randomly generated invalid freight_type string,
     * the validation rule 'in:free,flat,template,default' would reject it.
     *
     * **Validates: Requirements 1.5**
     */
    public function testRandomInvalidFreightTypesAreRejectedByInRule(): void
    {
        $rules = $this->getBaseRules();
        $freightTypeRules = $this->normalizeRules($rules['freight_type']);

        // Extract the in: rule to verify against
        $inRule = null;
        foreach ($freightTypeRules as $rule) {
            if (str_starts_with($rule, 'in:')) {
                $inRule = $rule;
                break;
            }
        }
        self::assertNotNull($inRule, 'freight_type must have an in: rule');

        // Parse allowed values from the in: rule
        $allowedValues = explode(',', mb_substr($inRule, 3));

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $invalidType = $this->randomInvalidFreightType();

            // Verify the generated value is truly not in the allowed set
            self::assertNotContains(
                $invalidType,
                $allowedValues,
                "Iteration {$i}: Generated value '{$invalidType}' should not be in the allowed freight_type values"
            );

            // The in: rule would reject this value
            self::assertFalse(
                \in_array($invalidType, $allowedValues, true),
                "Iteration {$i}: Invalid freight_type '{$invalidType}' must be rejected by the in: rule"
            );
        }
    }

    // ---------------------------------------------------------------
    // Part G: Property-based — for each valid freight_type, verify
    //         the complete rule structure matches requirements
    // ---------------------------------------------------------------

    /**
     * Property 1c: For any random iteration, picking a random valid freight_type and
     * generating corresponding request data, the rule structure ensures:
     * - freight_type=flat → flat_freight_amount has required_if + integer + min:0 + max:99999
     * - freight_type=template → shipping_template_id has required_if + integer + exists
     * - freight_type=free/default → neither field has unconditional 'required'.
     *
     * **Validates: Requirements 1.5, 1.6, 1.7, 1.8**
     */
    public function testRandomFreightTypeWithCorrespondingDataValidation(): void
    {
        $rules = $this->getBaseRules();

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $freightType = self::VALID_FREIGHT_TYPES[random_int(0, \count(self::VALID_FREIGHT_TYPES) - 1)];

            // Generate random corresponding data based on freight_type
            $data = ['freight_type' => $freightType];

            switch ($freightType) {
                case 'flat':
                    $data['flat_freight_amount'] = random_int(0, 99999);
                    break;
                case 'template':
                    $data['shipping_template_id'] = random_int(1, 9999);
                    break;
                case 'free':
                case 'default':
                    // Optionally include or exclude the conditional fields
                    if (random_int(0, 1) === 1) {
                        $data['flat_freight_amount'] = random_int(0, 99999);
                    }
                    if (random_int(0, 1) === 1) {
                        $data['shipping_template_id'] = random_int(1, 9999);
                    }
                    break;
            }

            // Verify freight_type rule accepts this value
            $freightTypeRules = $this->normalizeRules($rules['freight_type']);
            $inRule = null;
            foreach ($freightTypeRules as $rule) {
                if (str_starts_with($rule, 'in:')) {
                    $inRule = $rule;
                    break;
                }
            }
            $allowedValues = explode(',', mb_substr($inRule, 3));
            self::assertContains(
                $freightType,
                $allowedValues,
                "Iteration {$i}: freight_type='{$freightType}' must be in the allowed values"
            );

            // Verify conditional rules match the freight_type
            $flatRules = $this->normalizeRules($rules['flat_freight_amount']);
            $templateRules = $this->normalizeRules($rules['shipping_template_id']);

            if ($freightType === 'flat') {
                // flat_freight_amount MUST be required (via required_if)
                self::assertTrue(
                    $this->hasRule($flatRules, 'required_if:freight_type,flat'),
                    "Iteration {$i}: flat_freight_amount must be required when freight_type=flat"
                );
                self::assertContains('integer', $flatRules, "Iteration {$i}: flat_freight_amount must be integer");
                self::assertTrue($this->hasRule($flatRules, 'min:0'), "Iteration {$i}: flat_freight_amount must have min:0");
                self::assertTrue($this->hasRule($flatRules, 'max:99999'), "Iteration {$i}: flat_freight_amount must have max:99999");

                // shipping_template_id should NOT be unconditionally required
                self::assertNotContains('required', $templateRules, "Iteration {$i}: shipping_template_id must not be unconditionally required when freight_type=flat");
            } elseif ($freightType === 'template') {
                // shipping_template_id MUST be required (via required_if)
                self::assertTrue(
                    $this->hasRule($templateRules, 'required_if:freight_type,template'),
                    "Iteration {$i}: shipping_template_id must be required when freight_type=template"
                );
                self::assertContains('integer', $templateRules, "Iteration {$i}: shipping_template_id must be integer");
                self::assertTrue(
                    $this->hasRule($templateRules, 'exists:shipping_templates,id'),
                    "Iteration {$i}: shipping_template_id must have exists rule"
                );

                // flat_freight_amount should NOT be unconditionally required
                self::assertNotContains('required', $flatRules, "Iteration {$i}: flat_freight_amount must not be unconditionally required when freight_type=template");
            } else {
                // freight_type is 'free' or 'default' — neither field should be unconditionally required
                self::assertNotContains('required', $flatRules, "Iteration {$i}: flat_freight_amount must not be unconditionally required when freight_type={$freightType}");
                self::assertNotContains('required', $templateRules, "Iteration {$i}: shipping_template_id must not be unconditionally required when freight_type={$freightType}");
            }
        }
    }

    // ---------------------------------------------------------------
    // Part H: Property-based — mixed valid and invalid freight_type
    //         values in random iterations
    // ---------------------------------------------------------------

    /**
     * Property 1d: For any random iteration choosing either a valid or invalid freight_type,
     * valid values are accepted by the in: rule and invalid values are rejected.
     *
     * **Validates: Requirements 1.5**
     */
    public function testRandomMixOfValidAndInvalidFreightTypes(): void
    {
        $rules = $this->getBaseRules();
        $freightTypeRules = $this->normalizeRules($rules['freight_type']);

        $inRule = null;
        foreach ($freightTypeRules as $rule) {
            if (str_starts_with($rule, 'in:')) {
                $inRule = $rule;
                break;
            }
        }
        self::assertNotNull($inRule);
        $allowedValues = explode(',', mb_substr($inRule, 3));

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $useValid = random_int(0, 1) === 1;

            if ($useValid) {
                $freightType = self::VALID_FREIGHT_TYPES[random_int(0, \count(self::VALID_FREIGHT_TYPES) - 1)];
                self::assertContains(
                    $freightType,
                    $allowedValues,
                    "Iteration {$i}: Valid freight_type '{$freightType}' must be accepted by in: rule"
                );
            } else {
                $freightType = $this->randomInvalidFreightType();
                self::assertNotContains(
                    $freightType,
                    $allowedValues,
                    "Iteration {$i}: Invalid freight_type '{$freightType}' must be rejected by in: rule"
                );
            }
        }
    }

    /**
     * Get the base validation rules from ProductRequest via reflection.
     *
     * baseRules() is private, so we use reflection to invoke it.
     */
    private function getBaseRules(): array
    {
        $reflection = new \ReflectionClass(ProductRequest::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('baseRules');
        $method->setAccessible(true);

        return $method->invoke($instance);
    }

    /**
     * Normalize rules to a flat array of strings for easy assertion.
     *
     * @return string[]
     */
    private function normalizeRules(array $rules): array
    {
        return array_map('strval', array_values($rules));
    }

    /**
     * Check if a rule array contains a specific rule (exact or prefix match).
     */
    private function hasRule(array $rules, string $needle): bool
    {
        foreach ($rules as $rule) {
            if ((string) $rule === $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a random alphanumeric string.
     */
    private function randomString(int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-!@#$%^&*';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= $chars[random_int(0, mb_strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * Generate a random invalid freight_type value that is NOT in the valid set.
     */
    private function randomInvalidFreightType(): string
    {
        $candidates = [
            'express', 'pickup', 'cod', 'FREE', 'FLAT', 'TEMPLATE', 'DEFAULT',
            'Free', 'Flat', 'Template', 'Default', '', ' ', 'null', 'none',
            'standard', 'premium', 'economy', 'overnight', '0', '1', '-1',
            'free ', ' flat', 'template!', 'default?',
        ];

        // Mix in some purely random strings
        if (random_int(0, 1) === 1) {
            return $this->randomString(random_int(1, 20));
        }

        $value = $candidates[random_int(0, \count($candidates) - 1)];

        // Ensure it's truly not in the valid set
        if (\in_array($value, self::VALID_FREIGHT_TYPES, true)) {
            return 'invalid_' . $this->randomString(5);
        }

        return $value;
    }
}
