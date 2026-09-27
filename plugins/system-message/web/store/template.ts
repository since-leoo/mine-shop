/**
 * 模板状态管理
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { MessageTemplate, TemplateListParams, CreateTemplateData, UpdateTemplateData } from '../api/template'
import { templateApi } from '../api/template'

export const useTemplateStore = defineStore('message-template', () => {
  // 状态
  const templates = ref<MessageTemplate[]>([])
  const currentTemplate = ref<MessageTemplate | null>(null)
  const loading = ref(false)
  const total = ref(0)
  const currentPage = ref(1)
  const pageSize = ref(20)
  const categories = ref<Record<string, number>>({})
  const templateTypes = ref<Record<string, string>>({})

  // 计算属性
  const totalPages = computed(() => Math.ceil(total.value / pageSize.value))
  const activeTemplates = computed(() => templates.value.filter(t => t.is_active))
  const categoryList = computed(() => Object.keys(categories.value))

  // 操作
  const actions = {
    // 获取模板列表
    async getList(params: TemplateListParams = {}) {
      loading.value = true
      try {
        const response = await templateApi.getList({
          page: currentPage.value,
          page_size: pageSize.value,
          ...params
        })
        
        templates.value = response.data.data
        total.value = response.data.total
        currentPage.value = response.data.page
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 获取模板详情
    async getDetail(id: number) {
      loading.value = true
      try {
        const response = await templateApi.getDetail(id)
        currentTemplate.value = response.data
        return response
      } finally {
        loading.value = false
      }
    },

    // 创建模板
    async create(data: CreateTemplateData) {
      loading.value = true
      try {
        const response = await templateApi.create(data)
        // 刷新列表
        await actions.getList()
        return response
      } finally {
        loading.value = false
      }
    },

    // 更新模板
    async update(id: number, data: UpdateTemplateData) {
      loading.value = true
      try {
        const response = await templateApi.update(id, data)
        
        // 更新当前模板
        if (currentTemplate.value?.id === id) {
          currentTemplate.value = response.data
        }
        
        // 更新列表中的模板
        const index = templates.value.findIndex(t => t.id === id)
        if (index !== -1) {
          templates.value[index] = response.data
        }
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 删除模板
    async delete(id: number) {
      loading.value = true
      try {
        const response = await templateApi.delete(id)
        
        // 从列表中移除
        templates.value = templates.value.filter(t => t.id !== id)
        total.value--
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 预览模板
    async preview(id: number, variables: Record<string, any> = {}) {
      loading.value = true
      try {
        const response = await templateApi.preview(id, variables)
        return response
      } finally {
        loading.value = false
      }
    },

    // 渲染模板
    async render(id: number, variables: Record<string, any> = {}) {
      try {
        const response = await templateApi.render(id, variables)
        return response
      } catch (error) {
        console.error('Failed to render template:', error)
        throw error
      }
    },

    // 验证模板变量
    async validateVariables(id: number, variables: Record<string, any>) {
      try {
        const response = await templateApi.validateVariables(id, variables)
        return response
      } catch (error) {
        console.error('Failed to validate template variables:', error)
        throw error
      }
    },

    // 获取模板变量
    async getVariables(id: number) {
      try {
        const response = await templateApi.getVariables(id)
        return response
      } catch (error) {
        console.error('Failed to get template variables:', error)
        throw error
      }
    },

    // 复制模板
    async duplicate(id: number, name?: string) {
      loading.value = true
      try {
        const response = await templateApi.duplicate(id, name)
        // 刷新列表
        await actions.getList()
        return response
      } finally {
        loading.value = false
      }
    },

    // 激活/停用模板
    async toggleActive(id: number) {
      try {
        const response = await templateApi.toggleActive(id)
        
        // 更新本地状态
        const template = templates.value.find(t => t.id === id)
        if (template) {
          template.is_active = !template.is_active
        }
        
        if (currentTemplate.value?.id === id) {
          currentTemplate.value.is_active = !currentTemplate.value.is_active
        }
        
        return response
      } catch (error) {
        console.error('Failed to toggle template active status:', error)
        throw error
      }
    },

    // 批量删除模板
    async batchDelete(ids: number[]) {
      loading.value = true
      try {
        const response = await templateApi.batchDelete(ids)
        
        // 从列表中移除
        templates.value = templates.value.filter(t => !ids.includes(t.id))
        total.value -= ids.length
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 搜索模板
    async search(keyword: string, params: any = {}) {
      loading.value = true
      try {
        const response = await templateApi.search(keyword, {
          page: currentPage.value,
          page_size: pageSize.value,
          ...params
        })
        
        templates.value = response.data.data
        total.value = response.data.total
        currentPage.value = response.data.page
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 获取模板分类
    async getCategories() {
      try {
        const response = await templateApi.getCategories()
        categories.value = response.data
        return response
      } catch (error) {
        console.error('Failed to get template categories:', error)
        throw error
      }
    },

    // 获取活跃模板
    async getActiveTemplates(type?: string) {
      try {
        const response = await templateApi.getActiveTemplates(type)
        return response
      } catch (error) {
        console.error('Failed to get active templates:', error)
        throw error
      }
    },

    // 导入模板
    async import(templates: CreateTemplateData[]) {
      loading.value = true
      try {
        const response = await templateApi.import(templates)
        // 刷新列表
        await actions.getList()
        return response
      } finally {
        loading.value = false
      }
    },

    // 导出模板
    async export(ids: number[] = []) {
      try {
        const response = await templateApi.export(ids)
        return response
      } catch (error) {
        console.error('Failed to export templates:', error)
        throw error
      }
    },

    // 获取模板类型
    async getTemplateTypes() {
      try {
        const response = await templateApi.getTemplateTypes()
        templateTypes.value = response.data
        return response
      } catch (error) {
        console.error('Failed to get template types:', error)
        throw error
      }
    }
  }

  // 工具方法
  const utils = {
    // 根据ID查找模板
    findById(id: number): MessageTemplate | undefined {
      return templates.value.find(t => t.id === id)
    },

    // 根据名称查找模板
    findByName(name: string): MessageTemplate | undefined {
      return templates.value.find(t => t.name === name)
    },

    // 获取模板的变量列表
    getTemplateVariables(template: MessageTemplate): string[] {
      const titleVars = extractVariables(template.title_template)
      const contentVars = extractVariables(template.content_template)
      return [...new Set([...titleVars, ...contentVars])]
    },

    // 验证模板变量值
    validateVariableValues(template: MessageTemplate, variables: Record<string, any>): string[] {
      const errors: string[] = []
      const requiredVars = utils.getTemplateVariables(template)
      
      for (const varName of requiredVars) {
        if (!variables[varName] || variables[varName].toString().trim() === '') {
          errors.push(`变量 "${varName}" 不能为空`)
        }
      }
      
      return errors
    }
  }

  // 通用操作
  const setPage = (page: number) => {
    currentPage.value = page
  }

  const setPageSize = (size: number) => {
    pageSize.value = size
    currentPage.value = 1
  }

  const clearCurrentTemplate = () => {
    currentTemplate.value = null
  }

  const reset = () => {
    templates.value = []
    currentTemplate.value = null
    loading.value = false
    total.value = 0
    currentPage.value = 1
    categories.value = {}
    templateTypes.value = {}
  }

  return {
    // 状态
    templates,
    currentTemplate,
    loading,
    total,
    currentPage,
    pageSize,
    categories,
    templateTypes,
    
    // 计算属性
    totalPages,
    activeTemplates,
    categoryList,
    
    // 操作
    actions,
    utils,
    setPage,
    setPageSize,
    clearCurrentTemplate,
    reset
  }
})

// 辅助函数：提取模板变量
function extractVariables(template: string): string[] {
  const pattern = /\{\{([^}]+)\}\}/g
  const variables: string[] = []
  let match
  
  while ((match = pattern.exec(template)) !== null) {
    variables.push(match[1].trim())
  }
  
  return variables
}