import type { ResponseStruct } from '#/global'

export interface DictionaryTypeVo {
  // 主键
  id?: number
  // 分类名称
  name?: string
  // 分类编码
  code?: string
  // 状态
  status?: number
  // 备注信息
  remark?: string
  // 创建者
  created_by?: number
  // 更新者
  updated_by?: number
  // 创建时间
  created_at?: string
  // 更新时间
  updated_at?: string

  [key: string]: any
}

// 字典分类查询
export function list(params: DictionaryTypeVo): Promise<ResponseStruct<DictionaryTypeVo[]>> {
  return useHttp().get('/admin/data_center/dictionary_type/list', { params })
}

// 字典分类新增
export function create(data: DictionaryTypeVo): Promise<ResponseStruct<null>> {
  return useHttp().post('/admin/data_center/dictionary_type', data)
}

// 字典分类编辑
export function save(id: number, data: DictionaryTypeVo): Promise<ResponseStruct<null>> {
  return useHttp().put(`/admin/data_center/dictionary_type/${id}`, data)
}

// 字典分类删除
export function deleteByIds(ids: number[]): Promise<ResponseStruct<null>> {
  return useHttp().delete('/admin/data_center/dictionary_type', { data: ids })
}
