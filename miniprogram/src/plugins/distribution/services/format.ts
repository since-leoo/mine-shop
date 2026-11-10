import type { DistributionOverview, DistributionStatus } from './index';

/** All distribution API monetary fields are integer fen. */
export function money(value: number | string | undefined | null): string {
  const fen = Number(value ?? 0);
  return (Number.isFinite(fen) ? fen / 100 : 0).toFixed(2);
}

/** Parse user input without binary floating-point rounding. Invalid input returns null. */
export function yuanToFen(value: string): number | null {
  if (!/^\d+(?:\.\d{1,2})?$/.test(value.trim())) return null;
  const [whole, fraction = ''] = value.trim().split('.');
  const amount = Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
  return Number.isSafeInteger(amount) && amount > 0 ? amount : null;
}

export function distributionVisible(status: DistributionStatus | null | undefined, miniProgram: boolean): boolean {
  return status?.enabled === true && (miniProgram ? status.miniProgramEnabled !== false : status.h5Enabled !== false);
}

export function hasDistributionHistory(status: DistributionStatus | null, overview: DistributionOverview | null): boolean {
  return status?.hasAccount === true || status?.hasHistory === true || !!overview && [overview.pendingCommission, overview.availableCommission, overview.frozenCommission, overview.withdrawnCommission, overview.debtAmount].some(value => Number(value) > 0);
}

export function shortTime(value?: string): string { return value ? String(value).replace('T', ' ').slice(0, 16) : ''; }
export const commissionLabels: Record<string, string> = { pending: '待结算', settled: '已结算', cancelled: '已取消' };
export const withdrawalLabels: Record<string, string> = { pending: '待审核', approved: '待付款', paid: '已付款', rejected: '已驳回', failed: '付款失败' };
export const orderLabels: Record<string, string> = { pending: '待付款', paid: '已支付', shipped: '已发货', completed: '已完成', cancelled: '已取消', refunded: '已退款', closed: '已关闭', pending_payment: '待付款', pending_shipment: '待发货', pending_receipt: '待收货', 5: '待付款', 10: '待发货', 40: '待收货', 50: '已完成', 60: '待评价' };
