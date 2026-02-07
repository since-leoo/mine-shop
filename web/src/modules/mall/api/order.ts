/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()
const baseUrl = '/admin/order/order'

export interface OrderItemVo {
  id: number
  product_id: number
  sku_id?: number
  product_name: string
  sku_name?: string
  unit_price: number
  quantity: number
  total_price: number
  product_image?: string
}

export interface OrderAddressVo {
  name: string
  phone: string
  province: string
  city: string
  district: string
  address: string
}

export interface OrderPackageVo {
  id: number
  order_id: number
  package_no: string
  shipping_company?: string
  shipping_no?: string
  shipped_at?: string
  delivered_at?: string
  status: string
}

export interface OrderVo {
  id: number
  order_no: string
  member_id: number
  order_type: string
  status: string
  goods_amount: number
  shipping_fee: number
  discount_amount: number
  total_amount: number
  pay_amount: number
  pay_status: string
  pay_time?: string
  pay_no?: string
  pay_method?: string
  buyer_remark?: string
  seller_remark?: string
  shipping_status: string
  package_count: number
  expire_time?: string
  created_at: string
  updated_at: string
  shipped_at?: string
  completed_at?: string
  member?: { id: number; nickname: string; phone: string }
  items?: OrderItemVo[]
  address?: OrderAddressVo
  packages?: OrderPackageVo[]
}

export interface OrderListParams {
  page?: number
  page_size?: number
  order_no?: string
  pay_no?: string
  status?: string
  pay_status?: string
  member_id?: string | number
  member_phone?: string
  product_name?: string
  start_date?: string
  end_date?: string
}

export const orderApi = {
  list: (params?: OrderListParams) => http.get<{ list: OrderVo[]; total: number }>(
    `${baseUrl}/list`,
    { params },
  ),

  stats: (params?: { start_date?: string; end_date?: string }) => http.get<{
    total: number
    pending: number
    paid: number
    shipped: number
    completed: number
  }>(`${baseUrl}/stats`, { params }),

  detail: (id: number) => http.get<OrderVo>(`${baseUrl}/${id}`),

  ship: (id: number, data: { shipping_company: string; shipping_no: string; remark?: string }) => http.put<OrderVo>(
    `${baseUrl}/${id}/ship`,
    data,
  ),

  cancel: (id: number, reason?: string) => http.put<OrderVo>(`${baseUrl}/${id}/cancel`, { reason }),

  export: (params?: OrderListParams) => http.post<{ message: string }>(`${baseUrl}/export`, params),
}

export type { OrderVo as MallOrder, OrderListParams as MallOrderListParams }
