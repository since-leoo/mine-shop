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

namespace HyperfTests\Unit\Domain\Shipping;

use App\Domain\Trade\Shipping\Repository\ShippingTemplateRepository;
use App\Domain\Trade\Shipping\Service\FreightCalculationService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ShippingSetting;
use App\Infrastructure\Model\Shipping\ShippingTemplate;
use DG\BypassFinals;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * FreightCalculationService 属性测试.
 *
 * 使用 PHPUnit + Mockery + 循环随机数据模拟属性测试，每个属性至少 100 次迭代。
 *
 * @internal
 * @coversNothing
 */
final class FreightCalculationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    private const ALL_PROVINCES = [
        '北京', '天津', '河北', '山西', '内蒙古', '辽宁', '吉林', '黑龙江',
        '上海', '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南',
        '湖北', '湖南', '广东', '广西', '海南', '重庆', '四川', '贵州',
        '云南', '西藏', '陕西', '甘肃', '青海', '宁夏', '新疆',
    ];

    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    /**
     * @group Feature: shipping-freight-feature, Property 2: 运费类型解析回退
     *
     * Property 2: 运费类型解析回退
     * For any 商品，当 freight_type 不为 default 时，FreightCalculationService 使用商品自身的运费配置；
     * 当 freight_type=default 时，使用商城系统配置。
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    public function testProperty2FreightTypeFallback(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $productFlatAmount = random_int(0, 99999);
            $systemFlatAmount = random_int(0, 99999);
            $systemFreightType = ['free', 'flat'][random_int(0, 1)];
            $province = self::ALL_PROVINCES[array_rand(self::ALL_PROVINCES)];

            $shippingSetting = new ShippingSetting(
                defaultMethod: 'express',
                enablePickup: false,
                pickupAddress: '',
                freeShippingThreshold: 0,
                supportedProviders: [],
                defaultFreightType: $systemFreightType,
                flatFreightAmount: $systemFlatAmount,
                remoteAreaEnabled: false,
                remoteAreaSurcharge: 0,
                remoteAreaProvinces: [],
                defaultTemplateConfig: ['template_id' => 1],
            );

            $service = $this->createService($shippingSetting);

            // Case A: freight_type=flat (non-default) → uses product's own flat_freight_amount
            $result = $service->calculate('flat', $productFlatAmount, null, $province);
            self::assertSame(
                $productFlatAmount,
                $result,
                "Iteration {$i}: freight_type=flat should use product flat_freight_amount={$productFlatAmount}, got {$result}"
            );

            // Case B: freight_type=free (non-default) → uses product's own config (free = 0)
            $result = $service->calculate('free', $productFlatAmount, null, $province);
            self::assertSame(
                0,
                $result,
                "Iteration {$i}: freight_type=free should return 0, got {$result}"
            );

            // Case C: freight_type=default → falls back to system config
            $result = $service->calculate('default', $productFlatAmount, null, $province);
            $expectedFromSystem = match ($systemFreightType) {
                'free' => 0,
                'flat' => $systemFlatAmount,
                default => 0,
            };
            self::assertSame(
                $expectedFromSystem,
                $result,
                "Iteration {$i}: freight_type=default with system type={$systemFreightType} should return {$expectedFromSystem}, got {$result}"
            );
        }
    }

    /**
     * @group Feature: shipping-freight-feature, Property 3: 包邮类型运费为零
     *
     * Property 3: 包邮类型运费为零
     * For any 解析后运费类型为 free 的商品，在不考虑偏远地区加价的情况下，基础运费 SHALL 为 0。
     *
     * **Validates: Requirements 5.4**
     */
    public function testProperty3FreeFreightIsZero(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $province = self::ALL_PROVINCES[array_rand(self::ALL_PROVINCES)];
            $quantity = random_int(1, 100);
            $weight = random_int(0, 50000);
            $volume = random_int(0, 100000);
            $randomFlatAmount = random_int(0, 99999);

            // Disable remote area surcharge to test base freight only
            $shippingSetting = new ShippingSetting(
                defaultMethod: 'express',
                enablePickup: false,
                pickupAddress: '',
                freeShippingThreshold: 0,
                supportedProviders: [],
                defaultFreightType: 'free',
                flatFreightAmount: 0,
                remoteAreaEnabled: false,
                remoteAreaSurcharge: 0,
                remoteAreaProvinces: [],
                defaultTemplateConfig: [],
            );

            $service = $this->createService($shippingSetting);

            // Case A: Product freight_type=free directly
            $result = $service->calculate('free', $randomFlatAmount, null, $province, $quantity, $weight, $volume);
            self::assertSame(
                0,
                $result,
                "Iteration {$i}: freight_type=free should return 0, got {$result}"
            );

            // Case B: Product freight_type=default, system default_freight_type=free
            $result = $service->calculate('default', $randomFlatAmount, null, $province, $quantity, $weight, $volume);
            self::assertSame(
                0,
                $result,
                "Iteration {$i}: freight_type=default with system free should return 0, got {$result}"
            );
        }
    }

    /**
     * @group Feature: shipping-freight-feature, Property 4: 统一运费返回精确金额
     *
     * Property 4: 统一运费返回精确金额
     * For any 解析后运费类型为 flat 的商品，基础运费 SHALL 等于对应的 flat_freight_amount。
     *
     * **Validates: Requirements 5.5**
     */
    public function testProperty4FlatFreightReturnsExactAmount(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $productFlatAmount = random_int(0, 99999);
            $systemFlatAmount = random_int(0, 99999);
            $province = self::ALL_PROVINCES[array_rand(self::ALL_PROVINCES)];
            $quantity = random_int(1, 100);
            $weight = random_int(0, 50000);
            $volume = random_int(0, 100000);

            $shippingSetting = new ShippingSetting(
                defaultMethod: 'express',
                enablePickup: false,
                pickupAddress: '',
                freeShippingThreshold: 0,
                supportedProviders: [],
                defaultFreightType: 'flat',
                flatFreightAmount: $systemFlatAmount,
                remoteAreaEnabled: false,
                remoteAreaSurcharge: 0,
                remoteAreaProvinces: [],
                defaultTemplateConfig: [],
            );

            $service = $this->createService($shippingSetting);

            // Case A: Product freight_type=flat → uses product's flat_freight_amount
            $result = $service->calculate('flat', $productFlatAmount, null, $province, $quantity, $weight, $volume);
            self::assertSame(
                $productFlatAmount,
                $result,
                "Iteration {$i}: freight_type=flat should return product amount {$productFlatAmount}, got {$result}"
            );

            // Case B: Product freight_type=default, system default_freight_type=flat → uses system flat_freight_amount
            $result = $service->calculate('default', $productFlatAmount, null, $province, $quantity, $weight, $volume);
            self::assertSame(
                $systemFlatAmount,
                $result,
                "Iteration {$i}: freight_type=default with system flat should return system amount {$systemFlatAmount}, got {$result}"
            );
        }
    }

    /**
     * @group Feature: shipping-freight-feature, Property 5: 模板阶梯运费计算
     *
     * Property 5: 模板阶梯运费计算
     * For any 有效的运费模板和正整数计费单位值，阶梯计算结果 SHALL 满足：
     * 当 unitValue ≤ first_unit 时返回 first_price；
     * 当 unitValue > first_unit 时返回 first_price + ceil((unitValue - first_unit) / additional_unit) * additional_price。
     *
     * **Validates: Requirements 5.6**
     */
    public function testProperty5TemplateStepFreightCalculation(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $firstUnit = random_int(1, 50);
            $firstPrice = random_int(100, 5000);
            $additionalUnit = random_int(1, 20);
            $additionalPrice = random_int(50, 2000);
            $chargeType = ['weight', 'quantity', 'volume'][random_int(0, 2)];
            $province = self::ALL_PROVINCES[array_rand(self::ALL_PROVINCES)];

            // Generate unitValue: sometimes within first_unit, sometimes exceeding
            $unitValue = random_int(1, $firstUnit + 100);

            $rules = [
                [
                    'region_ids' => [$province],
                    'first_unit' => $firstUnit,
                    'first_price' => $firstPrice,
                    'additional_unit' => $additionalUnit,
                    'additional_price' => $additionalPrice,
                ],
            ];

            // Calculate expected result
            if ($unitValue <= $firstUnit) {
                $expected = $firstPrice;
            } else {
                $remaining = $unitValue - $firstUnit;
                $additionalCount = (int) ceil($remaining / $additionalUnit);
                $expected = $firstPrice + ($additionalCount * $additionalPrice);
            }

            // Create mock ShippingTemplate model
            $templateModel = \Mockery::mock(ShippingTemplate::class);
            $templateModel->shouldReceive('getAttribute')->with('id')->andReturn(1);
            $templateModel->shouldReceive('getAttribute')->with('name')->andReturn('Test Template');
            $templateModel->shouldReceive('getAttribute')->with('charge_type')->andReturn($chargeType);
            $templateModel->shouldReceive('getAttribute')->with('rules')->andReturn($rules);
            $templateModel->shouldReceive('getAttribute')->with('free_rules')->andReturn([]);
            $templateModel->shouldReceive('getAttribute')->with('is_default')->andReturn(false);
            $templateModel->shouldReceive('getAttribute')->with('status')->andReturn('active');

            $shippingSetting = new ShippingSetting(
                defaultMethod: 'express',
                enablePickup: false,
                pickupAddress: '',
                freeShippingThreshold: 0,
                supportedProviders: [],
                defaultFreightType: 'template',
                flatFreightAmount: 0,
                remoteAreaEnabled: false,
                remoteAreaSurcharge: 0,
                remoteAreaProvinces: [],
                defaultTemplateConfig: ['template_id' => 1],
            );

            $mallSettingService = \Mockery::mock(DomainMallSettingService::class);
            $mallSettingService->shouldReceive('shipping')->andReturn($shippingSetting);

            $templateRepository = \Mockery::mock(ShippingTemplateRepository::class);
            $templateRepository->shouldReceive('findById')->with(1)->andReturn($templateModel);

            $service = new FreightCalculationService($mallSettingService, $templateRepository);

            // Set the appropriate unit value based on charge_type
            $quantity = $chargeType === 'quantity' ? $unitValue : 1;
            $weight = $chargeType === 'weight' ? $unitValue : 0;
            $volume = $chargeType === 'volume' ? $unitValue : 0;

            $result = $service->calculate('template', 0, 1, $province, $quantity, $weight, $volume);
            self::assertSame(
                $expected,
                $result,
                "Iteration {$i}: chargeType={$chargeType}, unitValue={$unitValue}, firstUnit={$firstUnit}, "
                . "firstPrice={$firstPrice}, additionalUnit={$additionalUnit}, additionalPrice={$additionalPrice} "
                . "→ expected {$expected}, got {$result}"
            );

            \Mockery::close();
        }
    }

    /**
     * @group Feature: shipping-freight-feature, Property 6: 偏远地区加价
     *
     * Property 6: 偏远地区加价
     * For any 订单，当 remote_area_enabled=true 且收货省份在列表中时，
     * 最终运费 = 基础运费 + surcharge；否则最终运费 = 基础运费。
     *
     * **Validates: Requirements 5.7**
     */
    public function testProperty6RemoteAreaSurcharge(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $baseFlatAmount = random_int(0, 99999);
            $surcharge = random_int(100, 5000);
            $remoteAreaEnabled = (bool) random_int(0, 1);

            // Pick a random subset of provinces as remote areas
            $shuffled = self::ALL_PROVINCES;
            shuffle($shuffled);
            $remoteCount = random_int(1, 10);
            $remoteProvinces = \array_slice($shuffled, 0, $remoteCount);

            // Pick a random province for delivery
            $deliveryProvince = self::ALL_PROVINCES[array_rand(self::ALL_PROVINCES)];
            $isInRemoteArea = \in_array($deliveryProvince, $remoteProvinces, true);

            $shippingSetting = new ShippingSetting(
                defaultMethod: 'express',
                enablePickup: false,
                pickupAddress: '',
                freeShippingThreshold: 0,
                supportedProviders: [],
                defaultFreightType: 'flat',
                flatFreightAmount: 0,
                remoteAreaEnabled: $remoteAreaEnabled,
                remoteAreaSurcharge: $surcharge,
                remoteAreaProvinces: $remoteProvinces,
                defaultTemplateConfig: [],
            );

            $service = $this->createService($shippingSetting);

            // Use flat freight type so base freight = baseFlatAmount
            $result = $service->calculate('flat', $baseFlatAmount, null, $deliveryProvince);

            if ($remoteAreaEnabled && $isInRemoteArea) {
                $expected = $baseFlatAmount + $surcharge;
                self::assertSame(
                    $expected,
                    $result,
                    "Iteration {$i}: remote enabled + province '{$deliveryProvince}' in remote list → "
                    . "expected base({$baseFlatAmount}) + surcharge({$surcharge}) = {$expected}, got {$result}"
                );
            } else {
                self::assertSame(
                    $baseFlatAmount,
                    $result,
                    "Iteration {$i}: remote " . ($remoteAreaEnabled ? 'enabled' : 'disabled')
                    . " + province '{$deliveryProvince}' " . ($isInRemoteArea ? 'in' : 'not in') . ' remote list → '
                    . "expected base({$baseFlatAmount}), got {$result}"
                );
            }
        }
    }

    /**
     * Helper: create FreightCalculationService with mocked dependencies.
     * Used for tests that don't need template repository behavior.
     */
    private function createService(ShippingSetting $shippingSetting, ?ShippingTemplateRepository $templateRepository = null): FreightCalculationService
    {
        $mallSettingService = \Mockery::mock(DomainMallSettingService::class);
        $mallSettingService->shouldReceive('shipping')->andReturn($shippingSetting);

        $templateRepository ??= \Mockery::mock(ShippingTemplateRepository::class);

        return new FreightCalculationService($mallSettingService, $templateRepository);
    }
}
