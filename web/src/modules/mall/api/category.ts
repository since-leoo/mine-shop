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

export interface CategoryVo {
  id?: number
  name?: string
  parent_id?: number
  icon?: string
  thumbnail?: string
  description?: string
  sort?: number
  status?: string
  created_at?: string
  updated_at?: string
}

export interface CategorySearchVo {
  name?: string
  status?: string
  page?: number
  page_size?: number
  [key: string]: any
}

export function page(params: CategorySearchVo): Promise<ResponseStruct<PageList<CategoryVo>>> {
  return useHttp().get('/admin/product/category/list', { params })
}

export function create(data: CategoryVo): Promise<ResponseStruct<CategoryVo>> {
  return useHttp().post('/admin/product/category', data)
}

export function save(id: number, data: CategoryVo): Promise<ResponseStruct<CategoryVo>> {
  return useHttp().put(`/admin/product/category/${id}`, data)
}

export function remove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`/admin/product/category/${id}`)
}

export function tree(parent_id = 0): Promise<ResponseStruct<CategoryVo[]>> {
  return useHttp().get('/admin/product/category/tree', { params: { parent_id } })
}
