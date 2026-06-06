import type { PageList, ResponseStruct } from '#/global'
import type { DiyPageType, DiySchema } from './diyPage'

export interface DiyTemplateVo {
  id?: number
  category_id: number
  name: string
  page_key: string
  page_type: DiyPageType
  cover?: string | null
  description?: string | null
  schema: DiySchema
  sort?: number
  is_enabled?: boolean
  category?: { id?: number; name?: string; code?: string }
  created_at?: string
  updated_at?: string
}

export interface DiyTemplateSearchVo {
  name?: string
  category_id?: number | ''
  page_key?: string
  page_type?: DiyPageType | ''
  is_enabled?: boolean | ''
  page?: number
  page_size?: number
  [key: string]: any
}

const baseUrl = '/admin/diy/templates'

export function pageDiyTemplates(params: DiyTemplateSearchVo): Promise<ResponseStruct<PageList<DiyTemplateVo>>> {
  return useHttp().get(`${baseUrl}/list`, { params })
}

export function getDiyTemplate(id: number): Promise<ResponseStruct<DiyTemplateVo>> {
  return useHttp().get(`${baseUrl}/${id}`)
}

export function createDiyTemplate(data: Partial<DiyTemplateVo>): Promise<ResponseStruct<DiyTemplateVo>> {
  return useHttp().post(baseUrl, data)
}

export function updateDiyTemplate(id: number, data: Partial<DiyTemplateVo>): Promise<ResponseStruct<null>> {
  return useHttp().put(`${baseUrl}/${id}`, data)
}

export function enableDiyTemplate(id: number): Promise<ResponseStruct<null>> {
  return useHttp().post(`${baseUrl}/${id}/enable`)
}

export function disableDiyTemplate(id: number): Promise<ResponseStruct<null>> {
  return useHttp().post(`${baseUrl}/${id}/disable`)
}

export function applyDiyTemplate(id: number, pageId: number): Promise<ResponseStruct<any>> {
  return useHttp().post(`${baseUrl}/${id}/apply`, { page_id: pageId })
}
