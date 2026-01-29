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

export interface BrandVo {
  id?: number
  name?: string
  logo?: string
  description?: string
  website?: string
  sort?: number
  status?: string
  created_at?: string
  updated_at?: string
}

export interface BrandSearchVo {
  name?: string
  status?: string
  page?: number
  page_size?: number
  [key: string]: any
}

export function page(params: BrandSearchVo): Promise<ResponseStruct<PageList<BrandVo>>> {
  return useHttp().get('/admin/product/brand/list', { params })
}

export function create(data: BrandVo): Promise<ResponseStruct<BrandVo>> {
  return useHttp().post('/admin/product/brand', data)
}

export function save(id: number, data: BrandVo): Promise<ResponseStruct<BrandVo>> {
  return useHttp().put(`/admin/product/brand/${id}`, data)
}

export function remove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`/admin/product/brand/${id}`)
}

export function options(): Promise<ResponseStruct<BrandVo[]>> {
  return useHttp().get('/admin/product/brand/options')
}
