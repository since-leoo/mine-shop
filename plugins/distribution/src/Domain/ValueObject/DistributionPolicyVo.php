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

namespace Plugin\Distribution\Domain\ValueObject;

final class DistributionPolicyVo
{
    public const DIRECT_LIMIT = 3;

    public static function defaults(): array
    {
        return ['enabled' => false, 'h5_enabled' => true, 'mini_program_enabled' => true, 'commission_rate_bps' => 0, 'settlement_days' => 7, 'withdrawal_min_amount' => 100, 'withdrawal_fee_bps' => 0, 'commission_mode' => 'all_orders', 'excluded_product_ids' => [], 'product_rules' => [], 'activity_enabled' => false, 'h5_base_url' => ''];
    }

    public static function validate(array $settings): array
    {
        $s = array_replace(self::defaults(), array_intersect_key($settings, self::defaults()));
        foreach (['enabled', 'h5_enabled', 'mini_program_enabled', 'activity_enabled'] as $field) {
            $s[$field] = (bool) $s[$field];
        }
        foreach (['commission_rate_bps', 'settlement_days', 'withdrawal_min_amount', 'withdrawal_fee_bps'] as $field) {
            $s[$field] = (int) $s[$field];
        }
        $s['h5_base_url'] = trim((string) $s['h5_base_url']);
        $s['excluded_product_ids'] = array_map('intval', $s['excluded_product_ids']);
        foreach ($s['product_rules'] as &$rule) {
            $rule['product_id'] = (int) $rule['product_id'];
            $rule['sku_id'] = empty($rule['sku_id']) ? null : (int) $rule['sku_id'];
            $rule['value'] = (int) $rule['value'];
        }
        unset($rule);
        if ($s['commission_rate_bps'] < 0 || $s['commission_rate_bps'] > 10000 || $s['withdrawal_fee_bps'] < 0 || $s['withdrawal_fee_bps'] >= 10000 || $s['settlement_days'] < 0 || $s['settlement_days'] > 365 || $s['withdrawal_min_amount'] < 1) {
            throw new \DomainException('佣金、提现或结算参数不合法');
        }
        if (! \in_array($s['commission_mode'], ['all_orders', 'first_order'], true)) {
            throw new \DomainException('不支持的计佣模式');
        }
        $seen = [];
        foreach ($s['product_rules'] as $r) {
            $key = (int) $r['product_id'] . ':' . (int) ($r['sku_id'] ?? 0);
            if (isset($seen[$key])) {
                throw new \DomainException('同一商品规格不能重复配置佣金');
            }
            $seen[$key] = true;
            if (! \in_array($r['mode'], ['rate', 'fixed'], true) || $r['value'] < 0 || ($r['mode'] === 'rate' && $r['value'] > 10000)) {
                throw new \DomainException('商品佣金规则不合法');
            }
        }
        if ($s['h5_base_url'] !== '' && (! filter_var($s['h5_base_url'], \FILTER_VALIDATE_URL) || ! \in_array(parse_url($s['h5_base_url'], \PHP_URL_SCHEME), ['http', 'https'], true) || parse_url($s['h5_base_url'], \PHP_URL_QUERY) || parse_url($s['h5_base_url'], \PHP_URL_FRAGMENT))) {
            throw new \DomainException('H5地址应为不带参数和片段的完整站点地址');
        }
        return $s;
    }

    /** Integer allocation preserves every cent; same item order is used for all snapshots. */
    public static function allocate(int $total, array $weights): array
    {
        $sum = array_sum($weights);
        if ($total < 0 || min($weights ?: [0]) < 0 || ($sum === 0 && $total > 0)) {
            throw new \DomainException('商品金额分摊异常');
        }
        if ($sum === 0) {
            return array_fill(0, \count($weights), 0);
        }
        $result = [];
        $previous = 0;
        $cumulative = 0;
        foreach ($weights as $weight) {
            $cumulative += $weight;
            $allocated = intdiv($total * $cumulative, $sum);
            $result[] = $allocated - $previous;
            $previous = $allocated;
        }
        return $result;
    }

    public static function commission(array $settings, int $product, int $sku, int $quantity, int $base): int
    {
        if ($base <= 0 || \in_array($product, array_map('intval', $settings['excluded_product_ids']), true)) {
            return 0;
        }
        $rule = ['mode' => 'rate', 'value' => (int) $settings['commission_rate_bps']];
        $productRule = null;
        foreach ($settings['product_rules'] as $candidate) {
            if ((int) $candidate['product_id'] !== $product) {
                continue;
            }
            if ((int) ($candidate['sku_id'] ?? 0) === $sku && $sku > 0) {
                $rule = $candidate;
                $productRule = null;
                break;
            }
            if (empty($candidate['sku_id'])) {
                $productRule = $candidate;
            }
        }
        $rule = $productRule ?? $rule;
        return min($base, $rule['mode'] === 'fixed' ? (int) $rule['value'] * $quantity : intdiv($base * (int) $rule['value'], 10000));
    }

    public static function refundCommission(int $original, int $base, int $refundedGoods): int
    {
        if ($base <= 0) {
            return 0;
        }
        // Recalculate remaining entitlement, so a final refund always removes the rounding remainder.
        return $original - intdiv($original * max(0, $base - min($base, $refundedGoods)), $base);
    }
}
