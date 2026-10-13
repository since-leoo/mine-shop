/** API 金额以分计；折扣值 850 表示 8.5 折，所有券入口保持同一单位。 */
export function formatCouponAmount(value: unknown): string {
  const amount = Number(value || 0);
  return Number.isFinite(amount) ? (amount / 100).toFixed(2).replace(/\.?0+$/, '') : '0';
}

export function isDiscountCoupon(type: unknown): boolean {
  return type === 'discount' || type === 'percent' || type === 2;
}

export function formatCouponRule(detail: { type: unknown; value: number; base?: number }): string {
  const base = Number(detail.base || 0);
  const value = formatCouponAmount(detail.value);
  if (isDiscountCoupon(detail.type)) return `${base > 0 ? `满${formatCouponAmount(base)}元` : ''}${value}折`;
  return `${base > 0 ? `满${formatCouponAmount(base)}元` : ''}减${value}元`;
}
