import type { PageList, ResponseStruct } from '#/global'

export interface DiySelectorSearchVo {
  keyword?: string
  page?: number
  page_size?: number
  [key: string]: any
}

export interface DiyProductSelectorVo {
  id: number
  name: string
  main_image?: string | null
  min_price: number
  max_price: number
  status: string
  is_recommend?: boolean
  is_hot?: boolean
  is_new?: boolean
}

export interface DiyCategorySelectorVo {
  id: number
  parent_id: number
  name: string
  icon?: string | null
  thumbnail?: string | null
  level: number
  sort?: number
  status?: string
}

export interface DiyCouponSelectorVo {
  id: number
  name: string
  type?: string | null
  value: number
  min_amount: number
  total_quantity: number
  used_quantity: number
  start_time?: string | null
  end_time?: string | null
  status: string
}

export interface DiySeckillSelectorVo {
  id: number
  activity_id: number
  title: string
  start_time?: string | null
  end_time?: string | null
  status: string
  total_quantity: number
  sold_quantity: number
  is_enabled: boolean
}

export interface DiyGroupBuySelectorVo {
  id: number
  title: string
  product_id: number
  sku_id: number
  group_price: number
  min_people: number
  max_people: number
  start_time?: string | null
  end_time?: string | null
  status: string
  total_quantity: number
  sold_quantity: number
  is_enabled: boolean
}

const baseUrl = '/admin/diy/selectors'

export function selectDiyProducts(params: DiySelectorSearchVo): Promise<ResponseStruct<PageList<DiyProductSelectorVo>>> {
  return useHttp().get(`${baseUrl}/products`, { params })
}

export function selectDiyCategories(params: Pick<DiySelectorSearchVo, 'keyword'> = {}): Promise<ResponseStruct<DiyCategorySelectorVo[]>> {
  return useHttp().get(`${baseUrl}/categories`, { params })
}

export function selectDiyCoupons(params: DiySelectorSearchVo): Promise<ResponseStruct<PageList<DiyCouponSelectorVo>>> {
  return useHttp().get(`${baseUrl}/coupons`, { params })
}

export function selectDiySeckills(params: DiySelectorSearchVo): Promise<ResponseStruct<PageList<DiySeckillSelectorVo>>> {
  return useHttp().get(`${baseUrl}/seckills`, { params })
}

export function selectDiyGroupBuys(params: DiySelectorSearchVo): Promise<ResponseStruct<PageList<DiyGroupBuySelectorVo>>> {
  return useHttp().get(`${baseUrl}/group-buys`, { params })
}
