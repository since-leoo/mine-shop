import type { ResponseStruct } from '#/global'

export interface DictionaryVo {
  // 主键
  id?: number
  // 字典类型
  type_id?: number
  // 字典标签
  label?: string
  // 国际化
  i18n?: string
  // 国际化
  i18n_scope?: number
  // 字典值
  value?: string
  // 文字颜色
  color?: string
  // 字典编码
  code?: string
  // 排序
  sort?: number
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

// 数据字典查询
export function page(params: DictionaryVo): Promise<ResponseStruct<DictionaryVo[]>> {
  return useHttp().get('/admin/data_center/dictionary/list', { params })
}

// 数据字典新增
export function create(data: DictionaryVo): Promise<ResponseStruct<null>> {
  return useHttp().post('/admin/data_center/dictionary', data)
}

// 数据字典编辑
export function save(id: number, data: DictionaryVo): Promise<ResponseStruct<null>> {
  return useHttp().put(`/admin/data_center/dictionary/${id}`, data)
}

// 数据字典删除
export function deleteByIds(ids: number[]): Promise<ResponseStruct<null>> {
  return useHttp().delete('/admin/data_center/dictionary', { data: ids })
}
