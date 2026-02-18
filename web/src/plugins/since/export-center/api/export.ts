import type { PageList, ResponseStruct } from '#/global'

export interface ExportTaskVo {
  id?: number
  task_name?: string
  export_format?: string
  status?: string
  progress?: number
  file_name?: string
  file_size?: number
  error_message?: string
  retry_count?: number
  created_at?: string
  completed_at?: string
}

export function page(params: Record<string, any>): Promise<ResponseStruct<PageList<ExportTaskVo>>> {
  return useHttp().get('/admin/export/tasks', { params })
}

export function download(id: number): Promise<ResponseStruct<{ url: string, file_name: string }>> {
  return useHttp().get(`/admin/export/tasks/${id}/download`)
}

export function remove(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`/admin/export/tasks/${id}`)
}

export function progress(id: number): Promise<ResponseStruct<{ progress: number, status: string }>> {
  return useHttp().get(`/admin/export/tasks/${id}/progress`)
}
