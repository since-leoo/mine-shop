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

export interface ProductSkuVo {
  id?: number
  sku_code?: string
  sku_name?: string
  spec_values?: string[]
  image?: string
  cost_price?: number
  market_price?: number
  sale_price?: number
  stock?: number
  warning_stock?: number
  weight?: number
  status?: string
}

export interface ProductAttributeVo {
  id?: number
  attribute_name?: string
  value?: string
}

export interface ProductGalleryVo {
  id?: number
  image_url?: string
  alt_text?: string
  sort_order?: number
  is_primary?: boolean
}

export interface ProductVo {
  id?: number
  product_code?: string
  category_id?: number
  brand_id?: number
  name?: string
  sub_title?: string
  main_image?: string
  gallery_images?: string[]
  description?: string
  detail_content?: string
  attributes?: any[]
  category?: { id?: number; name?: string }
  brand?: { id?: number; name?: string }
  min_price?: number
  max_price?: number
  virtual_sales?: number
  real_sales?: number
  is_recommend?: boolean
  is_hot?: boolean
  is_new?: boolean
  freight_type?: string
  flat_freight_amount?: number
  shipping_template_id?: number
  sort?: number
  status?: string
  skus?: ProductSkuVo[]
  product_attributes?: ProductAttributeVo[]
  gallery?: ProductGalleryVo[]
  created_at?: string
  updated_at?: string
}

export interface ProductSearchVo {
  name?: string
  keyword?: string
  product_code?: string
  status?: string
  category_id?: number
  brand_id?: number
  is_recommend?: boolean
  is_hot?: boolean
  is_new?: boolean
  min_price?: number
  max_price?: number
  sales_min?: number
  sales_max?: number
  page?: number
  page_size?: number
  [key: string]: any
}

export function page(params: ProductSearchVo): Promise<ResponseStruct<PageList<ProductVo>>> {
  return useHttp().get('/admin/product/product/list', { params })
}

export function detail(id: number): Promise<ResponseStruct<ProductVo>> {
  return useHttp().get(`/admin/product/product/${id}`)
}

export function create(data: ProductVo): Promise<ResponseStruct<ProductVo>> {
  return useHttp().post('/admin/product/product', data)
}

export function save(id: number, data: ProductVo): Promise<ResponseStruct<ProductVo>> {
  return useHttp().put(`/admin/product/product/${id}`, data)
}

export function remove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`/admin/product/product/${id}`)
}

export function stats(): Promise<ResponseStruct<{
  total: number
  active: number
  draft: number
  inactive: number
  sold_out: number
  warning_stock: number
}>> {
  return useHttp().get('/admin/product/product/stats')
}
