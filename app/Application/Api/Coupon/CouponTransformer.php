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

namespace App\Application\Api\Coupon;

use App\Infrastructure\Model\Coupon\Coupon;

final class CouponTransformer
{
    public function transformListItem(Coupon $coupon, int $receivedQuantity = 0): array
    {
        return $this->basePayload($coupon, $receivedQuantity);
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
            ? (float) $coupon->value
            : $this->toCent($coupon->value);
    }

    private function formatDiscountValue(Coupon $coupon): int
    {
        if ($this->normalizeType($coupon) === 'discount') {
            return (int) round(((float) $coupon->value) * 10);
        }

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
            $discount = rtrim(rtrim(number_format((float) $coupon->value, 1, '.', ''), '0'), '.');
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

    private function formatAmount(mixed $value): string
    {
        $number = (float) $value;
        $formatted = number_format($number, 2, '.', '');
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

    private function toCent(mixed $price): int
    {
        if (\is_string($price)) {
            $price = (float) $price;
        }

        if (! \is_int($price) && ! \is_float($price)) {
            $price = 0;
        }

        return (int) round(((float) $price) * 100);
    }
}
