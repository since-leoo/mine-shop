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

export interface CouponVo {
  id?: number
  name?: string
  type?: 'fixed' | 'percent'
  value?: number
  min_amount?: number
  total_quantity?: number
  used_quantity?: number
  per_user_limit?: number
  start_time?: string
  end_time?: string
  status?: 'active' | 'inactive'
  description?: string
  issued_quantity?: number
  created_at?: string
  updated_at?: string
}

export interface CouponSearchVo {
  name?: string
  type?: string
  status?: string
  start_time?: string
  end_time?: string
  page?: number
  page_size?: number
}

const couponBase = '/admin/coupon'

export function couponPage(params: CouponSearchVo): Promise<ResponseStruct<PageList<CouponVo>>> {
  return useHttp().get(`${couponBase}/list`, { params })
}

export function couponStats(): Promise<ResponseStruct<{ total: number; active: number; inactive: number }>> {
  return useHttp().get(`${couponBase}/stats`)
}

export function couponDetail(id: number): Promise<ResponseStruct<CouponVo>> {
  return useHttp().get(`${couponBase}/${id}`)
}

export function couponCreate(data: CouponVo): Promise<ResponseStruct<CouponVo>> {
  return useHttp().post(couponBase, data)
}

export function couponUpdate(id: number, data: CouponVo): Promise<ResponseStruct<CouponVo>> {
  return useHttp().put(`${couponBase}/${id}`, data)
}

export function couponRemove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`${couponBase}/${id}`)
}

export function couponToggleStatus(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${couponBase}/${id}/toggle-status`)
}

export function couponIssue(id: number, data: { member_ids: number[]; expire_at?: string }): Promise<ResponseStruct<any>> {
  return useHttp().post(`${couponBase}/${id}/issue`, data)
}

export interface CouponUserVo {
  id?: number
  coupon_id?: number
  member_id?: number
  order_id?: number
  status?: 'unused' | 'used' | 'expired'
  received_at?: string
  used_at?: string
  expire_at?: string
  coupon_name?: string
  member_nickname?: string
  member_phone?: string
  member?: Record<string, any>
  coupon?: CouponVo
}

export interface CouponUserSearchVo {
  coupon_id?: number
  member_id?: number
  status?: string
  keyword?: string
  page?: number
  page_size?: number
}

const couponUserBase = '/admin/coupon/user'

export function couponUserPage(params: CouponUserSearchVo): Promise<ResponseStruct<PageList<CouponUserVo>>> {
  return useHttp().get(`${couponUserBase}/list`, { params })
}

export function couponUserMarkUsed(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${couponUserBase}/${id}/mark-used`)
}

export function couponUserMarkExpired(id: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`${couponUserBase}/${id}/mark-expired`)
}
