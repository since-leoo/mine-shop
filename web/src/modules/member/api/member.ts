/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code.
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

const memberBaseUrl = '/admin/member/member'
const memberTagBaseUrl = '/admin/member/tag'
const memberLevelBaseUrl = '/admin/member/level'
const memberAccountBaseUrl = '/admin/member/account'

export interface MemberTag {
  id: number
  name: string
  color?: string
  status: string
}

export interface MemberWallet {
  balance: number
  frozen_balance: number
  total_recharge: number
  total_consume: number
  status: string
}

export interface MemberAddress {
  id: number
  name: string
  phone: string
  province: string
  city: string
  district: string
  detail: string
  full_address: string
  is_default: boolean
  created_at?: string
}

export interface MemberVo {
  id: number
  openid: string
  unionid?: string
  nickname?: string
  avatar?: string
  gender?: string
  phone?: string
  birthday?: string
  city?: string
  province?: string
  district?: string
  street?: string
  region_path?: string
  country?: string
  level?: string
  growth_value?: number
  total_orders: number
  total_amount: number
  last_login_at?: string
  last_login_ip?: string
  status: string
  source?: string
  remark?: string
  created_at?: string
  updated_at?: string
  tags?: MemberTag[]
  wallet?: MemberWallet | null
  points_wallet?: MemberWallet | null
  addresses?: MemberAddress[]
  points_balance?: number
  points_total?: number
  level_id?: number
}

export interface MemberListParams {
  page?: number
  page_size?: number
  keyword?: string
  phone?: string
  status?: string
  level?: string
  source?: string
  tag_id?: number
  created_start?: string
  created_end?: string
  last_login_start?: string
  last_login_end?: string
}

export interface MemberOverviewParams {
  status?: string
  level?: string
  source?: string
  tag_id?: number
  created_start?: string
  created_end?: string
  trend_days?: number
}

export interface MemberBreakdownItem {
  key: string
  label: string
  value: number
}

export interface MemberOverviewTrend {
  labels: string[]
  new_members: number[]
  active_members: number[]
}

export interface MemberOverviewResponse {
  trend: MemberOverviewTrend
  source_breakdown: MemberBreakdownItem[]
  region_breakdown: MemberBreakdownItem[]
  level_breakdown: MemberBreakdownItem[]
}

export const memberApi = {
  list: (params?: MemberListParams) =>
    http.get<{ list: MemberVo[]; total: number }>(`${memberBaseUrl}/list`, { params }),

  stats: (params?: Partial<MemberListParams>) =>
    http.get<{ total: number; new_today: number; active_30d: number; sleeping_30d: number; banned: number }>(
      `${memberBaseUrl}/stats`,
      { params },
    ),

  overview: (params?: MemberOverviewParams) => http.get<MemberOverviewResponse>(`${memberBaseUrl}/overview`, { params }),

  detail: (id: number) => http.get<MemberVo>(`${memberBaseUrl}/${id}`),

  create: (data: Partial<MemberVo>) => http.post(memberBaseUrl, data),

  update: (id: number, data: Partial<MemberVo>) => http.put(`${memberBaseUrl}/${id}`, data),

  updateStatus: (id: number, status: string) => http.put(`${memberBaseUrl}/${id}/status`, { status }),

  syncTags: (id: number, tags: number[]) => http.put(`${memberBaseUrl}/${id}/tags`, { tags }),
}

export interface MemberTagPayload {
  name: string
  color?: string
  description?: string
  status?: string
  sort_order?: number
}

export const memberTagApi = {
  list: (params?: { page?: number; page_size?: number; keyword?: string; status?: string }) =>
    http.get<{ list: MemberTag[]; total: number }>(`${memberTagBaseUrl}/list`, { params }),

  options: () => http.get<MemberTag[]>(`${memberTagBaseUrl}/options`),

  create: (data: MemberTagPayload) => http.post<MemberTag>(memberTagBaseUrl, data),

  update: (id: number, data: MemberTagPayload) => http.put<MemberTag>(`${memberTagBaseUrl}/${id}`, data),

  delete: (id: number) => http.delete(`${memberTagBaseUrl}/${id}`),
}

export interface MemberLevel {
  id: number
  name: string
  level: number
  growth_value_min: number
  growth_value_max?: number
  discount_rate?: number
  point_rate?: number
  privileges?: Record<string, any>
  icon?: string
  color?: string
  status: string
  sort_order?: number
  description?: string
  created_at?: string
  updated_at?: string
}

export interface MemberLevelParams {
  page?: number
  page_size?: number
  keyword?: string
  status?: string
}

export const memberLevelApi = {
  list: (params?: MemberLevelParams) =>
    http.get<{ list: MemberLevel[]; total: number }>(`${memberLevelBaseUrl}/list`, { params }),

  detail: (id: number) => http.get<MemberLevel>(`${memberLevelBaseUrl}/${id}`),

  create: (data: Partial<MemberLevel>) => http.post<MemberLevel>(memberLevelBaseUrl, data),

  update: (id: number, data: Partial<MemberLevel>) => http.put<MemberLevel>(`${memberLevelBaseUrl}/${id}`, data),

  delete: (id: number) => http.delete(`${memberLevelBaseUrl}/${id}`),
}

export interface MemberAccountLogParams {
  page?: number
  page_size?: number
  member_id?: number
  wallet_type?: 'balance' | 'points'
  source?: string
  operator_type?: string
  start_date?: string
  end_date?: string
}

export interface MemberWalletLog {
  id: number
  member_id: number
  wallet_type: 'balance' | 'points'
  transaction_no: string
  type: string
  amount: number
  balance_before: number
  balance_after: number
  source?: string
  remark?: string
  operator_type?: string
  operator_name?: string
  created_at: string
}

export interface MemberAccountAdjustPayload {
  member_id: number
  value: number
  type: 'balance' | 'points'
  source?: string
  remark?: string
}

export const memberAccountApi = {
  walletLogs: (params?: MemberAccountLogParams) =>
    http.get<{ list: MemberWalletLog[]; total: number }>(`${memberAccountBaseUrl}/wallet/logs`, { params }),

  adjustWallet: (data: MemberAccountAdjustPayload) => http.post(`${memberAccountBaseUrl}/wallet/adjust`, data),
}

export type {
  MemberVo as MallMember,
  MemberListParams as MallMemberListParams,
  MemberOverviewResponse,
  MemberBreakdownItem,
}
