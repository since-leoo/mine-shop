import { request } from '../../../services/request';

export interface DistributionStatus {
  enabled: boolean;
  canInvite: boolean;
  inviteCode: string;
  directCount: number;
  directLimit: number;
  remainingSlots: number;
  h5Enabled: boolean;
  miniProgramEnabled: boolean;
  hasAccount?: boolean;
  hasHistory?: boolean;
}
export interface DistributionOverview {
  directCount: number;
  teamCount: number;
  consumerCount: number;
  paidOrderCount: number;
  validOrderCount: number;
  paidAmount: number;
  refundAmount: number;
  netAmount: number;
  pendingCommission: number;
  availableCommission: number;
  frozenCommission: number;
  withdrawnCommission: number;
  debtAmount: number;
}
export type TeamScope = 'direct' | 'all';
export interface DistributionMember {
  memberId: number;
  nickname: string;
  avatar?: string;
  depth: number;
  joinedAt: string;
  paidOrderCount: number;
  validOrderCount: number;
  netAmount: number;
}
export interface DistributionOrder {
  id: number;
  orderNo: string;
  memberId: number;
  nickname: string;
  productSummary: string;
  paidAmount: number;
  refundAmount: number;
  netAmount: number;
  status: string;
  paidAt: string;
  commissionAmount: number;
}
export interface CommissionRecord {
  id: number;
  orderId: number;
  orderNo: string;
  amount: number;
  refundedAmount: number;
  netAmount: number;
  status: 'pending' | 'settled' | 'cancelled';
  createdAt: string;
  settledAt?: string;
}
export interface WithdrawalRecord {
  id: number;
  withdrawalNo: string;
  amount: number;
  feeAmount: number;
  netAmount: number;
  status: 'pending' | 'approved' | 'paid' | 'rejected' | 'failed';
  remark?: string;
  paymentNo?: string;
  createdAt: string;
  paidAt?: string;
}
export interface WithdrawalPolicy { minAmount: number; feeRateBps: number; channels: string[] }
export interface PageResult<T> { list: T[]; total: number }
export interface ListQuery { page?: number; pageSize?: number; scope?: TeamScope | 'self'; memberId?: number }

const base = '/api/v1/distribution';
const get = <T>(path: string, data: Record<string, any> = {}): Promise<T> => request({ url: `${base}/${path}`, data, needAuth: true });
export const fetchDistributionStatus = () => get<DistributionStatus>('status');
export const fetchDistributionOverview = () => get<DistributionOverview>('overview');
export const fetchDistributionTeam = (query: ListQuery) => get<PageResult<DistributionMember>>('team', query);
export const fetchDistributionOrders = (query: ListQuery) => get<PageResult<DistributionOrder>>('orders', query);
export const fetchDistributionCommissions = (query: ListQuery) => get<PageResult<CommissionRecord>>('commissions', query);
export const fetchDistributionWithdrawals = (query: ListQuery) => get<PageResult<WithdrawalRecord>>('withdrawals', query);
export const fetchWithdrawalPolicy = () => get<WithdrawalPolicy>('withdrawal-policy');
export const submitWithdrawal = (data: { amount: number; channel: 'manual'; accountName: string; accountNo: string; remark?: string; requestKey?: string }) => request({ url: `${base}/withdrawals`, method: 'POST', data, needAuth: true });
