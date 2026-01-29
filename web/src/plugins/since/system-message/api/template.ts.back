/**
 * 模板相关API接口
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

// 模板类型定义
export interface MessageTemplate {
  id: number
  name: string
  title_template: string
  content_template: string
  type: string
  category: string
  description?: string
  variables: string[]
  is_active: boolean
  created_by: number
  created_at: string
  updated_at: string
}

export interface TemplateListParams {
  page?: number
  page_size?: number
  type?: string
  category?: string
  is_active?: boolean
  created_by?: number
  date_from?: string
  date_to?: string
  keyword?: string
}

export interface CreateTemplateData {
  name: string
  title_template: string
  content_template: string
  type: string
  category: string
  description?: string
  variables?: string[]
  is_active?: boolean
}

export interface UpdateTemplateData extends Partial<CreateTemplateData> {}

export interface TemplatePreviewData {
  template: MessageTemplate
  variables: Record<string, any>
  rendered: {
    title: string
    content: string
  }
  preview_html: string
}

export interface TemplateValidationResult {
  valid: boolean
  errors: string[]
  missing_variables: string[]
  extra_variables: string[]
}

// 模板管理API
export const templateApi = {
  // 获取模板列表
  getList(params: TemplateListParams = {}) {
    return http.get('/plugin/admin/system-message/template/index', { params })
  },

  // 获取模板详情
  getDetail(id: number) {
    return http.get(`/plugin/admin/system-message/template/read/${id}`)
  },

  // 创建模板
  create(data: CreateTemplateData) {
    return http.post('/plugin/admin/system-message/template/save', data)
  },

  // 更新模板
  update(id: number, data: UpdateTemplateData) {
    return http.put(`/plugin/admin/system-message/template/update/${id}`, data)
  },

  // 删除模板
  delete(id: number) {
    return http.delete('/plugin/admin/system-message/template/delete', {
      data: { ids: [id] }
    })
  },

  // 预览模板
  preview(id: number, variables: Record<string, any> = {}): Promise<TemplatePreviewData> {
    return http.post('/plugin/admin/system-message/template/preview', {
      id,
      variables
    })
  },

  // 渲染模板
  render(id: number, variables: Record<string, any> = {}) {
    return http.post('/plugin/admin/system-message/template/render', {
      id,
      variables
    })
  },

  // 验证模板变量
  validateVariables(id: number, variables: Record<string, any>): Promise<TemplateValidationResult> {
    return http.post('/plugin/admin/system-message/template/validateVariables', {
      id,
      variables
    })
  },

  // 获取模板变量
  getVariables(id: number): Promise<string[]> {
    return http.get(`/plugin/admin/system-message/template/getVariables/${id}`)
  },

  // 复制模板
  duplicate(id: number, name?: string) {
    return http.post('/plugin/admin/system-message/template/copy', {
      id,
      name
    })
  },

  // 激活/停用模板
  toggleActive(id: number) {
    return http.put('/plugin/admin/system-message/template/changeStatus', {
      id
    })
  },

  // 批量删除模板
  batchDelete(ids: number[]) {
    return http.delete('/plugin/admin/system-message/template/delete', {
      data: { ids }
    })
  },

  // 搜索模板
  search(keyword: string, params: Omit<TemplateListParams, 'keyword'> = {}) {
    return http.get('/plugin/admin/system-message/template/search', {
      params: { keyword, ...params }
    })
  },

  // 获取模板分类
  getCategories() {
    return http.get('/plugin/admin/system-message/template/categories')
  },

  // 获取活跃模板
  getActiveTemplates(type?: string) {
    return http.get('/plugin/admin/system-message/template/active', {
      params: type ? { type } : {}
    })
  },

  // 导入模板
  import(templates: CreateTemplateData[]) {
    return http.post('/plugin/admin/system-message/template/import', {
      templates
    })
  },

  // 导出模板
  export(ids: number[] = []) {
    return http.post('/plugin/admin/system-message/template/export', {
      ids
    })
  },

  // 获取模板统计
  getStatistics() {
    return http.get('/plugin/admin/system-message/template/statistics')
  },

  // 获取模板类型列表
  getTemplateTypes() {
    return http.get('/plugin/admin/system-message/template/types')
  }
}