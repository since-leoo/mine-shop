import { describe, expect, it } from 'vitest';
import { formatCouponAmount, formatCouponRule } from './presentation';

describe('优惠券 API 单位', () => {
  it('分转元不把门槛和优惠额四舍五入为整元', () => {
    expect(formatCouponAmount(1999)).toBe('19.99');
    expect(formatCouponAmount(1050)).toBe('10.5');
    expect(formatCouponAmount(10000)).toBe('100');
    expect(formatCouponAmount(0)).toBe('0');
  });
  it('折扣类型使用真实 API value 850', () => {
    expect(formatCouponRule({ type: 'discount', value: 850, base: 1999 })).toBe('满19.99元8.5折');
    expect(formatCouponRule({ type: 'price', value: 1050, base: 1999 })).toBe('满19.99元减10.5元');
  });
});
