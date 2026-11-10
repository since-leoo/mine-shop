import type { ResponseStruct } from '#/global'
import useHttp from '@/hooks/auto-imports/useHttp'

export interface ProductRule {
  product_id: number
  sku_id?: number | null
  mode: 'rate' | 'fixed'
  value: number
}
export interface DistributionSettings {
  enabled: boolean
  h5_enabled: boolean
  mini_program_enabled: boolean
  h5_base_url: string
  commission_rate_bps: number
  settlement_days: number
  withdrawal_min_amount: number
  withdrawal_fee_bps: number
  commission_mode: 'all_orders' | 'first_order'
  excluded_product_ids: number[]
  product_rules: ProductRule[]
  activity_enabled: boolean
}
export interface DistributionMember {
  id: number
  member_id?: number
  nickname?: string
  mobile?: string
  parent_id?: number | null
  invite_code?: string
  direct_count?: number
  team_count?: number
  order_count?: number
  net_consumption_amount?: number
  created_at?: string
}
export interface DistributionOrder {
  id: number
  order_no: string
  member_id: number
  member_nickname?: string
  paid_amount: number
  refund_amount: number
  net_amount: number
  status: string
  paid_at?: string
}
export interface DistributionCommission {
  id: number
  member_id: number
  order_id: number
  order_no: string
  amount: number
  refunded_amount: number
  net_amount: number
  status: 'pending' | 'settled' | 'cancelled'
  created_at: string
  settled_at?: string
}
export type WithdrawalAction = 'approve' | 'reject' | 'pay' | 'fail'
export interface DistributionWithdrawal {
  id: number
  member_id: number
  withdrawal_no: string
  amount: number
  fee_amount: number
  net_amount: number
  status: 'pending' | 'approved' | 'paid' | 'rejected' | 'failed'
  channel: string
  account_name: string
  account_no: string
  remark?: string
  payment_no?: string
  created_at: string
  paid_at?: string
}
export interface DistributionLog {
  id: number
  operator_id: number
  action: string
  subject_id: number
  detail: string | Record<string, unknown>
  created_at: string
}
export interface DistributionPage<T> { list: T[], total: number }
export type DistributionQuery = Record<string, string | number | undefined>
export type DistributionRow = DistributionMember | DistributionOrder | DistributionCommission | DistributionWithdrawal | DistributionLog
const base = '/admin/distribution'
export const getSettings = (): Promise<ResponseStruct<DistributionSettings>> => useHttp().get(`${base}/settings`)
export const saveSettings = (data: DistributionSettings): Promise<ResponseStruct<DistributionSettings>> => useHttp().put(`${base}/settings`, data)
export const getOverview = (params: DistributionQuery): Promise<ResponseStruct<Record<string, number>>> => useHttp().get(`${base}/overview`, { params })
export const getList = (type: string, params: DistributionQuery): Promise<ResponseStruct<DistributionPage<DistributionRow>>> => useHttp().get(`${base}/${type}`, { params })
export const getTeam = (id: number, params: DistributionQuery): Promise<ResponseStruct<DistributionPage<DistributionMember>>> => useHttp().get(`${base}/members/${id}/team`, { params })
export const reviewWithdrawal = (id: number, data: { action: WithdrawalAction, remark?: string, payment_no?: string }): Promise<ResponseStruct<unknown>> => useHttp().post(`${base}/withdrawals/${id}/review`, data)
