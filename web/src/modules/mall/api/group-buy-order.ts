import type { PageList, ResponseStruct } from '#/global'

export interface GroupBuyOrderSummaryVo {
  id?: number
  title?: string
  product_name?: string
  product_image?: string
  status?: string
  start_time?: string
  end_time?: string
  min_people?: number
  group_count?: number
  success_group_count?: number
  group_buyer_count?: number
  original_buyer_count?: number
  group_buyer_amount?: number
  total_quantity?: number
  sold_quantity?: number
}

export interface GroupBuyOrderDetailVo {
  id?: number
  group_no?: string
  is_leader?: boolean
  member_nickname?: string
  member_phone?: string
  quantity?: number
  original_price?: number
  group_price?: number
  total_amount?: number
  status?: string
  join_time?: string
  expire_time?: string
  order_no?: string
  order_status?: string
  pay_amount?: number
}

export function summaryPage(params: Record<string, any>): Promise<ResponseStruct<PageList<GroupBuyOrderSummaryVo>>> {
  return useHttp().get('/admin/group-buy-order/list', { params })
}

export function ordersByActivity(activityId: number, params: Record<string, any>): Promise<ResponseStruct<PageList<GroupBuyOrderDetailVo>>> {
  return useHttp().get(`/admin/group-buy-order/${activityId}/orders`, { params })
}
