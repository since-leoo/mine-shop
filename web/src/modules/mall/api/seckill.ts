/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { PageList, ResponseStruct } from '#/global'

// ============== Activity ==============
export interface SeckillActivityVo {
  id?: number
  title?: string
  description?: string
  status?: string
  is_enabled?: boolean
  rules?: Record<string, any>
  remark?: string
  sessions_count?: number
  created_at?: string
  updated_at?: string
}

export interface SeckillActivitySearchVo {
  title?: string
  keyword?: string
  status?: string
  is_enabled?: boolean
  page?: number
  page_size?: number
}

const activityBase = '/admin/seckill/activity'

export function activityPage(params: SeckillActivitySearchVo): Promise<ResponseStruct<PageList<SeckillActivityVo>>> {
  return useHttp().get(`${activityBase}/list`, { params })
}

export function activityStats(): Promise<ResponseStruct<{ total: number; enabled: number; disabled: number }>> {
  return useHttp().get(`${activityBase}/stats`)
}

export function activityDetail(id: number): Promise<ResponseStruct<SeckillActivityVo>> {
  return useHttp().get(`${activityBase}/${id}`)
}

export function activityCreate(data: SeckillActivityVo): Promise<ResponseStruct<SeckillActivityVo>> {
  return useHttp().post(activityBase, data)
}

export function activityUpdate(id: number, data: SeckillActivityVo): Promise<ResponseStruct<SeckillActivityVo>> {
  return useHttp().put(`${activityBase}/${id}`, data)
}

export function activityRemove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`${activityBase}/${id}`)
}

export function activityToggleStatus(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${activityBase}/${id}/toggle-status`)
}

// ============== Session ==============
export interface SeckillSessionVo {
  id?: number
  activity_id?: number
  start_time?: string
  end_time?: string
  status?: string
  max_quantity_per_user?: number
  total_quantity?: number
  sold_quantity?: number
  sort_order?: number
  is_enabled?: boolean
  rules?: Record<string, any>
  remark?: string
  products_count?: number
  activity?: SeckillActivityVo
  created_at?: string
  updated_at?: string
}

export interface SeckillSessionSearchVo {
  activity_id?: number
  status?: string
  is_enabled?: boolean
  page?: number
  page_size?: number
}

const sessionBase = '/admin/seckill/session'

export function sessionPage(params: SeckillSessionSearchVo): Promise<ResponseStruct<PageList<SeckillSessionVo>>> {
  return useHttp().get(`${sessionBase}/list`, { params })
}

export function sessionByActivity(activityId: number): Promise<ResponseStruct<SeckillSessionVo[]>> {
  return useHttp().get(`${sessionBase}/by-activity/${activityId}`)
}

export function sessionDetail(id: number): Promise<ResponseStruct<SeckillSessionVo>> {
  return useHttp().get(`${sessionBase}/${id}`)
}

export function sessionCreate(data: SeckillSessionVo): Promise<ResponseStruct<SeckillSessionVo>> {
  return useHttp().post(sessionBase, data)
}

export function sessionUpdate(id: number, data: SeckillSessionVo): Promise<ResponseStruct<SeckillSessionVo>> {
  return useHttp().put(`${sessionBase}/${id}`, data)
}

export function sessionRemove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`${sessionBase}/${id}`)
}

export function sessionToggleStatus(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${sessionBase}/${id}/toggle-status`)
}

// ============== Product ==============
export interface SeckillProductVo {
  id?: number
  activity_id?: number
  session_id?: number
  product_id?: number
  product_sku_id?: number
  original_price?: number
  seckill_price?: number
  quantity?: number
  sold_quantity?: number
  max_quantity_per_user?: number
  sort_order?: number
  is_enabled?: boolean
  product_name?: string
  product_image?: string
  sku_name?: string
  sku_code?: string
  sku_stock?: number
  created_at?: string
  updated_at?: string
}

export interface SeckillProductSearchVo {
  session_id?: number
  activity_id?: number
  product_id?: number
  is_enabled?: boolean
  page?: number
  page_size?: number
}

const productBase = '/admin/seckill/product'

export function productPage(params: SeckillProductSearchVo): Promise<ResponseStruct<PageList<SeckillProductVo>>> {
  return useHttp().get(`${productBase}/list`, { params })
}

export function productBySession(sessionId: number): Promise<ResponseStruct<SeckillProductVo[]>> {
  return useHttp().get(`${productBase}/by-session/${sessionId}`)
}

export function productDetail(id: number): Promise<ResponseStruct<SeckillProductVo>> {
  return useHttp().get(`${productBase}/${id}`)
}

export function productCreate(data: SeckillProductVo): Promise<ResponseStruct<SeckillProductVo>> {
  return useHttp().post(productBase, data)
}

export function productBatchCreate(data: { activity_id: number; session_id: number; products: SeckillProductVo[] }): Promise<ResponseStruct<SeckillProductVo[]>> {
  return useHttp().post(`${productBase}/batch`, data)
}

export function productUpdate(id: number, data: SeckillProductVo): Promise<ResponseStruct<SeckillProductVo>> {
  return useHttp().put(`${productBase}/${id}`, data)
}

export function productRemove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`${productBase}/${id}`)
}

export function productToggleStatus(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${productBase}/${id}/toggle-status`)
}
