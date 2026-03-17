import type { PageList, ResponseStruct } from '#/global'

export type AfterSaleType = 'refund_only' | 'return_refund' | 'exchange'
export type AfterSaleStatus =
  | 'pending_review'
  | 'waiting_buyer_return'
  | 'waiting_seller_receive'
  | 'waiting_refund'
  | 'refunding'
  | 'waiting_reship'
  | 'reshipped'
  | 'completed'
  | 'closed'

export interface AfterSaleVo {
  id: number
  after_sale_no: string
  order_id: number
  order_no?: string
  order_item_id: number
  member_id: number
  type: AfterSaleType
  status: AfterSaleStatus
  refund_status: 'none' | 'pending' | 'processing' | 'refunded'
  return_status: 'not_required' | 'pending' | 'buyer_shipped' | 'seller_received' | 'seller_reshipped' | 'buyer_received'
  apply_amount: number
  refund_amount: number
  quantity: number
  reason: string
  description?: string | null
  images?: string[]
  buyer_return_logistics_company?: string | null
  buyer_return_logistics_no?: string | null
  reship_logistics_company?: string | null
  reship_logistics_no?: string | null
  created_at?: string
  updated_at?: string
  product?: {
    productId?: number
    skuId?: number
    productName?: string
    skuName?: string
    productImage?: string
  }
}

export interface AfterSaleSearchVo {
  page?: number
  page_size?: number
  after_sale_no?: string
  order_no?: string
  member_id?: number | string
  type?: AfterSaleType | ''
  status?: AfterSaleStatus | ''
}

export interface AfterSaleReviewPayload {
  approved_refund_amount?: number
  remark?: string
  reject_reason?: string
}

export interface AfterSaleReshipPayload {
  logistics_company: string
  logistics_no: string
}

const afterSaleBase = '/admin/order/after-sale'
const http = useHttp()

export function afterSalePage(params: AfterSaleSearchVo): Promise<ResponseStruct<PageList<AfterSaleVo>>> {
  return http.get(`${afterSaleBase}/list`, { params })
}

export function afterSaleDetail(id: number): Promise<ResponseStruct<AfterSaleVo>> {
  return http.get(`${afterSaleBase}/${id}`)
}

export function afterSaleApprove(id: number, data: AfterSaleReviewPayload): Promise<ResponseStruct<null>> {
  return http.put(`${afterSaleBase}/${id}/approve`, data)
}

export function afterSaleReject(id: number, data: AfterSaleReviewPayload): Promise<ResponseStruct<null>> {
  return http.put(`${afterSaleBase}/${id}/reject`, data)
}

export function afterSaleReceive(id: number): Promise<ResponseStruct<null>> {
  return http.put(`${afterSaleBase}/${id}/receive`)
}

export function afterSaleRefund(id: number): Promise<ResponseStruct<null>> {
  return http.put(`${afterSaleBase}/${id}/refund`)
}

export function afterSaleReship(id: number, data: AfterSaleReshipPayload): Promise<ResponseStruct<null>> {
  return http.put(`${afterSaleBase}/${id}/reship`, data)
}

export function afterSaleCompleteExchange(id: number): Promise<ResponseStruct<null>> {
  return http.put(`${afterSaleBase}/${id}/complete-exchange`)
}
