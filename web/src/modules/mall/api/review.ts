import type { PageList, ResponseStruct } from '#/global'

export interface ReviewVo {
  id?: number
  order_id?: number
  order_item_id?: number
  product_id?: number
  sku_id?: number
  member_id?: number
  rating?: number
  content?: string
  images?: string[]
  is_anonymous?: boolean
  status?: 'pending' | 'approved' | 'rejected'
  admin_reply?: string
  reply_time?: string
  created_at?: string
  product_name?: string
  member_nickname?: string
  order_no?: string
}

export interface ReviewStatsVo {
  today_reviews?: number
  pending_reviews?: number
  total_reviews?: number
  average_rating?: number
}

const reviewBase = '/admin/review'

export function reviewPage(params: Record<string, any>): Promise<ResponseStruct<PageList<ReviewVo>>> {
  return useHttp().get(`${reviewBase}/list`, { params })
}

export function reviewDetail(id: number): Promise<ResponseStruct<ReviewVo>> {
  return useHttp().get(`${reviewBase}/${id}`)
}

export function reviewApprove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${reviewBase}/${id}/approve`)
}

export function reviewReject(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${reviewBase}/${id}/reject`)
}

export function reviewReply(id: number, data: { content: string }): Promise<ResponseStruct<null>> {
  return useHttp().put(`${reviewBase}/${id}/reply`, data)
}

export function reviewStats(): Promise<ResponseStruct<ReviewStatsVo>> {
  return useHttp().get(`${reviewBase}/stats`)
}

export function reviewsByOrder(orderId: number): Promise<ResponseStruct<ReviewVo[]>> {
  return useHttp().get(`${reviewBase}/by-order/${orderId}`)
}
