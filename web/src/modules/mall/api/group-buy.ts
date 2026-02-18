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

export interface GroupBuyVo {
  id?: number
  title?: string
  description?: string
  product_id?: number
  sku_id?: number
  original_price?: number
  group_price?: number
  min_people?: number
  max_people?: number
  start_time?: string
  end_time?: string
  group_time_limit?: number
  status?: string
  total_quantity?: number
  sold_quantity?: number
  group_count?: number
  success_group_count?: number
  sort_order?: number
  is_enabled?: boolean
  rules?: any
  images?: any
  remark?: string
  product?: any
  sku?: any
  created_at?: string
  updated_at?: string
}

export interface GroupBuySearchVo {
  keyword?: string
  status?: string
  is_enabled?: boolean
  product_id?: number
  page?: number
  page_size?: number
  [key: string]: any
}

export function page(params: GroupBuySearchVo): Promise<ResponseStruct<PageList<GroupBuyVo>>> {
  return useHttp().get('/admin/group-buy/list', { params })
}

export function detail(id: number): Promise<ResponseStruct<GroupBuyVo>> {
  return useHttp().get(`/admin/group-buy/${id}`)
}

export function create(data: GroupBuyVo): Promise<ResponseStruct<any>> {
  return useHttp().post('/admin/group-buy', data)
}

export function save(id: number, data: GroupBuyVo): Promise<ResponseStruct<any>> {
  return useHttp().put(`/admin/group-buy/${id}`, data)
}

export function remove(id: number): Promise<ResponseStruct<any>> {
  return useHttp().delete(`/admin/group-buy/${id}`)
}

export function toggleStatus(id: number): Promise<ResponseStruct<any>> {
  return useHttp().put(`/admin/group-buy/${id}/toggle-status`)
}

export function stats(): Promise<ResponseStruct<any>> {
  return useHttp().get('/admin/group-buy/stats')
}

export function groupBuyExport(params?: Record<string, any>): Promise<ResponseStruct<{ task_id: number, status: string }>> {
  return useHttp().post('/admin/group-buy/export', params)
}
