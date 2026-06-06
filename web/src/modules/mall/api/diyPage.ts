import type { PageList, ResponseStruct } from '#/global'

export type DiyPageType = 'miniprogram' | 'h5' | 'all'

export interface DiyComponent {
  id: string
  type: string
  name?: string
  enabled?: boolean
  props?: Record<string, any>
  style?: Record<string, any>
  data?: Record<string, any>
}

export interface DiySchema {
  version: 1
  page: {
    key: string
    title?: string
    [key: string]: any
  }
  components: DiyComponent[]
}

export interface DiyPageVo {
  id?: number
  page_key: string
  title: string
  page_type: DiyPageType
  description?: string | null
  is_enabled?: boolean
  status?: string
  published_version_id?: number | null
  versions?: Array<{ id: number; status: string; schema: DiySchema; updated_at?: string }>
  published_version?: { id: number; status: string; schema: DiySchema; published_at?: string }
  created_at?: string
  updated_at?: string
}

export interface DiyPageSearchVo {
  title?: string
  page_key?: string
  page_type?: DiyPageType | ''
  is_enabled?: boolean | ''
  status?: string
  page?: number
  page_size?: number
  [key: string]: any
}

const baseUrl = '/admin/diy/pages'

export function pageDiyPages(params: DiyPageSearchVo): Promise<ResponseStruct<PageList<DiyPageVo>>> {
  return useHttp().get(`${baseUrl}/list`, { params })
}

export function createDiyPage(data: Partial<DiyPageVo>): Promise<ResponseStruct<DiyPageVo>> {
  return useHttp().post(baseUrl, data)
}

export function getDiyPage(id: number): Promise<ResponseStruct<DiyPageVo>> {
  return useHttp().get(`${baseUrl}/${id}`)
}

export function updateDiyPage(id: number, data: Partial<DiyPageVo>): Promise<ResponseStruct<null>> {
  return useHttp().put(`${baseUrl}/${id}`, data)
}

export function saveDiyDraft(id: number, schema: DiySchema): Promise<ResponseStruct<any>> {
  return useHttp().put(`${baseUrl}/${id}/draft`, { schema })
}

export function publishDiyPage(id: number): Promise<ResponseStruct<any>> {
  return useHttp().post(`${baseUrl}/${id}/publish`)
}

export function enableDiyPage(id: number): Promise<ResponseStruct<null>> {
  return useHttp().post(`${baseUrl}/${id}/enable`)
}

export function disableDiyPage(id: number): Promise<ResponseStruct<null>> {
  return useHttp().post(`${baseUrl}/${id}/disable`)
}

export function copyDiyPage(id: number): Promise<ResponseStruct<DiyPageVo>> {
  return useHttp().post(`${baseUrl}/${id}/copy`)
}

export function resetDiyDraft(id: number): Promise<ResponseStruct<any>> {
  return useHttp().post(`${baseUrl}/${id}/reset`)
}
