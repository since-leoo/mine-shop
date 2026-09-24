import type { PageList, ResponseStruct } from '#/global'

export interface WecomTag { id: number; tag_id?: string; group_id?: string; name: string; color?: string; created_at?: string }

export function getTags(params: Record<string, unknown> = {}): Promise<ResponseStruct<PageList<WecomTag>>> {
  return useHttp().get('/admin/wecom-scrm/tags', { params })
}

export function saveTag(payload: Partial<WecomTag>): Promise<ResponseStruct<WecomTag>> {
  return payload.id ? useHttp().put(`/admin/wecom-scrm/tags/${payload.id}`, payload) : useHttp().post('/admin/wecom-scrm/tags', payload)
}

export function deleteTag(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`/admin/wecom-scrm/tags/${id}`)
}
