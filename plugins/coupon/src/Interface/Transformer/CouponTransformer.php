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

namespace Plugin\Since\Coupon\Interface\Transformer;

use Plugin\Since\Coupon\Infrastructure\Model\Coupon;

final class CouponTransformer
{
    public function transformListItem(Coupon $coupon, int $receivedQuantity = 0): array
    {
        return $this->basePayload($coupon, $receivedQuantity);
    }

    /**
     * 将 coupon_user 记录（含 coupon 关联）转换为 TDesign 小程序优惠券卡片格式.
     */
    public function transformMemberCouponItem(array $couponUser): array
    {
        $coupon = $couponUser['coupon'] ?? [];
        $type = ($coupon['type'] ?? '') === 'percent' ? 'discount' : 'price';
        $value = (int) ($coupon['value'] ?? 0);

        return [
            'key' => (string) ($couponUser['id'] ?? ''),
            'status' => $this->mapMemberCouponStatus($couponUser['status'] ?? 'unused'),
            'type' => $type,
            'value' => $type === 'discount' ? $value : $value,
            'tag' => $type === 'discount' ? '折扣' : '满减',
            'desc' => $this->buildMemberCouponDesc($coupon),
            'base' => (int) ($coupon['min_amount'] ?? 0),
            'title' => (string) ($coupon['name'] ?? ''),
            'timeLimit' => $this->buildMemberCouponTimeLimit($couponUser),
            'currency' => '¥',
        ];
    }

    /**
     * 将 Coupon 模型转换为 TDesign 小程序优惠券详情格式.
     */
    public function transformMiniDetail(Coupon $coupon): array
    {
        $type = $this->normalizeType($coupon);
        return [
            'key' => (string) $coupon->id,
            'status' => $this->resolveStatus($coupon),
            'type' => $type,
            'value' => $this->resolveValue($coupon),
            'tag' => $this->buildTag($coupon),
            'desc' => $this->buildLabel($coupon),
            'base' => $this->toCent($coupon->min_amount),
            'title' => (string) ($coupon->name ?? ''),
            'timeLimit' => $this->formatTimeLimit($coupon),
            'currency' => '¥',
            'useNotes' => (string) ($coupon->description ?? ''),
            'storeAdapt' => '商城通用',
        ];
    }

    private function mapMemberCouponStatus(string $status): string
    {
        return match ($status) {
            'unused' => 'default',
            'used' => 'useless',
            'expired' => 'disabled',
            default => 'disabled',
        };
    }

    private function buildMemberCouponDesc(array $coupon): string
    {
        $minAmount = (int) ($coupon['min_amount'] ?? 0);
        $type = ($coupon['type'] ?? '') === 'percent' ? 'discount' : 'price';
        $value = (int) ($coupon['value'] ?? 0);
        $minYuan = $this->formatAmount($minAmount);

        if ($type === 'discount') {
            $discount = rtrim(rtrim(number_format($value / 100, 1, '.', ''), '0'), '.');
            return $minYuan === '0' ? \sprintf('%s折', $discount) : \sprintf('满%s享%s折', $minYuan, $discount);
        }

        $discountYuan = $this->formatAmount($value);
        return $minYuan === '0' ? \sprintf('立减%s', $discountYuan) : \sprintf('满%s减%s', $minYuan, $discountYuan);
    }

    private function buildMemberCouponTimeLimit(array $couponUser): string
    {
        $coupon = $couponUser['coupon'] ?? [];
        $start = $coupon['start_time'] ?? null;
        $end = $coupon['end_time'] ?? ($couponUser['expire_at'] ?? null);
        if (! $start || ! $end) {
            return '';
        }
        $startDate = \is_string($start) ? substr($start, 0, 10) : '';
        $endDate = \is_string($end) ? substr($end, 0, 10) : '';
        return str_replace('-', '.', $startDate) . '-' . str_replace('-', '.', $endDate);
    }

    public function transformDetail(Coupon $coupon, int $receivedQuantity = 0): array
    {
        $payload = $this->basePayload($coupon, $receivedQuantity);
        $payload['title'] = (string) ($coupon->name ?? '');
        $payload['value'] = $this->resolveValue($coupon);
        $payload['base'] = $this->toCent($coupon->min_amount);
        $payload['desc'] = $this->buildLabel($coupon);
        $payload['currency'] = '¥';
        $payload['time_limit'] = $this->formatTimeLimit($coupon);
        $payload['use_notes'] = (string) ($coupon->description ?? '');
        $payload['store_adapt'] = '商城通用';
        $payload['status'] = $this->resolveStatus($coupon);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function basePayload(Coupon $coupon, int $receivedQuantity): array
    {
        $availableQuantity = max(0, (int) $coupon->total_quantity - (int) $coupon->used_quantity);
        $perUserLimit = (int) $coupon->per_user_limit;
        $isReceivable = $availableQuantity > 0 && ($perUserLimit === 0 || $receivedQuantity < $perUserLimit);

        return [
            'coupon_id' => (string) $coupon->id,
            'name' => (string) $coupon->name,
            'type' => $this->normalizeType($coupon),
            'discount_value' => $this->formatDiscountValue($coupon),
            'threshold_amount' => $this->toCent($coupon->min_amount),
            'tag' => $this->buildTag($coupon),
            'label' => $this->buildLabel($coupon),
            'description' => (string) ($coupon->description ?? ''),
            'start_time' => $coupon->start_time?->toAtomString(),
            'end_time' => $coupon->end_time?->toAtomString(),
            'available_quantity' => $availableQuantity,
            'total_quantity' => (int) $coupon->total_quantity,
            'per_user_limit' => $perUserLimit,
            'received_quantity' => $receivedQuantity,
            'is_receivable' => $isReceivable,
        ];
    }

    private function resolveValue(Coupon $coupon): float|int
    {
        return $this->normalizeType($coupon) === 'discount'
            ? (int) $coupon->value
            : $this->toCent($coupon->value);
    }

    private function formatDiscountValue(Coupon $coupon): int
    {
        if ($this->normalizeType($coupon) === 'discount') {
            // percent类型value存储为850表示8.5折，直接返回
            return (int) $coupon->value;
        }

        // fixed类型value已经是分
        return $this->toCent($coupon->value);
    }

    private function normalizeType(Coupon $coupon): string
    {
        return (string) ($coupon->type === 'percent' ? 'discount' : 'price');
    }

    private function buildTag(Coupon $coupon): string
    {
        return $this->normalizeType($coupon) === 'discount' ? '折扣' : '满减';
    }

    private function buildLabel(Coupon $coupon): string
    {
        $minAmount = $this->formatAmount($coupon->min_amount ?? 0);
        if ($this->normalizeType($coupon) === 'discount') {
            // value 850 → 8.5折
            $discount = rtrim(rtrim(number_format((int) $coupon->value / 100, 1, '.', ''), '0'), '.');
            return $minAmount === '0'
                ? \sprintf('%s折', $discount)
                : \sprintf('满%s享%s折', $minAmount, $discount);
        }

        $discountValue = $this->formatAmount($coupon->value ?? 0);
        if ($minAmount === '0') {
            return \sprintf('立减%s', $discountValue);
        }

        return \sprintf('满%s减%s', $minAmount, $discountValue);
    }

    /**
     * 分转元显示字符串，如 9990 → '99.9', 10000 → '100'.
     */
    private function formatAmount(mixed $value): string
    {
        $cents = (int) $value;
        $yuan = $cents / 100;
        $formatted = number_format($yuan, 2, '.', '');
        $trimmed = rtrim(rtrim($formatted, '0'), '.');
        return $trimmed === '' ? '0' : $trimmed;
    }

    private function resolveStatus(Coupon $coupon): string
    {
        return $coupon->status === 'active' ? 'default' : 'disabled';
    }

    private function formatTimeLimit(Coupon $coupon): string
    {
        if ($coupon->start_time === null || $coupon->end_time === null) {
            return '';
        }

        return \sprintf(
            '%s-%s',
            $coupon->start_time->format('Y.m.d'),
            $coupon->end_time->format('Y.m.d')
        );
    }

    /**
     * 确保金额为整数（分）。数据库已存储为分，无需转换.
     */
    private function toCent(mixed $price): int
    {
        if (\is_string($price)) {
            return (int) $price;
        }

        if (! \is_int($price) && ! \is_float($price)) {
            return 0;
        }

        return (int) $price;
    }
}
