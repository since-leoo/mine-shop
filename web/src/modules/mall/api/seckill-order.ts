import type { PageList, ResponseStruct } from '#/global'

export interface SeckillOrderSummaryVo {
  id?: number
  title?: string
  status?: string
  is_enabled?: boolean
  sessions_count?: number
  buyer_count?: number
  total_amount?: number
  created_at?: string
}

export interface SeckillOrderDetailVo {
  id?: number
  member_nickname?: string
  member_phone?: string
  session_time?: string
  quantity?: number
  original_price?: number
  seckill_price?: number
  total_amount?: number
  status?: string
  seckill_time?: string
  order_no?: string
  order_status?: string
  pay_amount?: number
}

export function summaryPage(params: Record<string, any>): Promise<ResponseStruct<PageList<SeckillOrderSummaryVo>>> {
  return useHttp().get('/admin/seckill-order/list', { params })
}

export function ordersByActivity(activityId: number, params: Record<string, any>): Promise<ResponseStruct<PageList<SeckillOrderDetailVo>>> {
  return useHttp().get(`/admin/seckill-order/${activityId}/orders`, { params })
}
