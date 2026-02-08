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

namespace App\Domain\Trade\Shipping\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Shipping\Mapper\ShippingTemplateMapper;
use App\Domain\Trade\Shipping\Repository\ShippingTemplateRepository;

/**
 * 运费计算领域服务：根据商品运费配置和系统配置计算订单运费.
 */
final class FreightCalculationService
{
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly ShippingTemplateRepository $templateRepository,
    ) {}

    /**
     * 计算单个商品的运费.
     *
     * @param string $freightType 商品的 freight_type
     * @param int $flatFreightAmount 商品的 flat_freight_amount
     * @param null|int $shippingTemplateId 商品的 shipping_template_id
     * @param string $province 收货省份
     * @param int $quantity 商品数量
     * @param int $weight 商品重量(g)
     * @param int $volume 商品体积(cm³)
     * @return int 运费金额（分）
     */
    public function calculate(
        string $freightType,
        int $flatFreightAmount,
        ?int $shippingTemplateId,
        string $province,
        int $quantity = 1,
        int $weight = 0,
        int $volume = 0,
    ): int {
        $shipping = $this->mallSettingService->shipping();

        // 解析实际运费类型
        $resolvedType = $freightType;
        $resolvedFlatAmount = $flatFreightAmount;
        $resolvedTemplateId = $shippingTemplateId;

        if ($freightType === 'default') {
            $resolvedType = $shipping->defaultFreightType();
            $resolvedFlatAmount = $shipping->flatFreightAmount();
            $resolvedTemplateId = $shipping->defaultTemplateConfig()['template_id'] ?? null;
        }

        // 按类型计算基础运费
        $freight = match ($resolvedType) {
            'free' => 0,
            'flat' => $resolvedFlatAmount,
            'template' => $this->calculateByTemplate($resolvedTemplateId, $province, $quantity, $weight, $volume),
            default => 0,
        };

        // 偏远地区加价
        if ($shipping->remoteAreaEnabled() && $this->isRemoteArea($province, $shipping->remoteAreaProvinces())) {
            $freight += $shipping->remoteAreaSurcharge();
        }

        return $freight;
    }

    private function calculateByTemplate(?int $templateId, string $province, int $quantity, int $weight, int $volume): int
    {
        if ($templateId === null) {
            return 0;
        }

        $template = $this->templateRepository->findById($templateId);
        if ($template === null) {
            return 0;
        }

        $entity = ShippingTemplateMapper::fromModel($template);
        $rules = $entity->getRules();
        $chargeType = $entity->getChargeType();

        // 找到匹配收货地区的规则
        $matchedRule = $this->findMatchingRule($rules, $province);
        if ($matchedRule === null) {
            return 0;
        }

        // 根据 charge_type 确定计费单位值
        $unitValue = match ($chargeType) {
            'weight' => $weight,
            'quantity' => $quantity,
            'volume' => $volume,
            default => $quantity,
        };

        // 计算阶梯运费
        return $this->calculateStepFreight($matchedRule, $unitValue);
    }

    /**
     * 查找匹配收货地区的运费规则.
     *
     * @param null|array<int, array<string, mixed>> $rules 运费规则列表
     * @param string $province 收货省份
     * @return null|array<string, mixed> 匹配的规则
     */
    private function findMatchingRule(?array $rules, string $province): ?array
    {
        if ($rules === null || $rules === []) {
            return null;
        }

        foreach ($rules as $rule) {
            $regionIds = $rule['region_ids'] ?? [];
            if (\in_array($province, $regionIds, true)) {
                return $rule;
            }
        }

        // 未匹配到则取第一条规则作为默认
        return $rules[0] ?? null;
    }

    /**
     * 计算阶梯运费.
     *
     * @param array<string, mixed> $rule 运费规则
     * @param int $unitValue 计费单位值
     * @return int 运费金额（分）
     */
    private function calculateStepFreight(array $rule, int $unitValue): int
    {
        $firstUnit = $rule['first_unit'] ?? 1;
        $firstPrice = $rule['first_price'] ?? 0;
        $additionalUnit = $rule['additional_unit'] ?? 1;
        $additionalPrice = $rule['additional_price'] ?? 0;

        if ($unitValue <= $firstUnit) {
            return $firstPrice;
        }

        $remaining = $unitValue - $firstUnit;
        $additionalCount = $additionalUnit > 0 ? (int) ceil($remaining / $additionalUnit) : 0;

        return $firstPrice + ($additionalCount * $additionalPrice);
    }

    /**
     * 判断是否为偏远地区.
     *
     * @param string $province 收货省份
     * @param string[] $remoteProvinces 偏远地区省份列表
     */
    private function isRemoteArea(string $province, array $remoteProvinces): bool
    {
        return \in_array($province, $remoteProvinces, true);
    }
}
