<script setup lang="ts">
import type { DiyPageType } from '~/mall/api/diyPage'
import type { DiyTemplateVo } from '~/mall/api/diyTemplate'
import { createDiyTemplate, updateDiyTemplate } from '~/mall/api/diyTemplate'
import { createDefaultSchema } from '../schema/componentRegistry'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'mall:diy:template:form' })

const { formType = 'add', data = null } = defineProps<{
  formType?: 'add' | 'edit'
  data?: DiyTemplateVo | null
}>()

const elFormRef = ref()
const model = reactive({
  category_id: 1,
  name: '',
  page_key: 'home',
  page_type: 'all' as DiyPageType,
  cover: '',
  description: '',
  sort: 0,
  is_enabled: true,
})

const rules = {
  category_id: [{ required: true, message: '请填写分类ID', trigger: 'change' }],
  name: [{ required: true, message: '请填写模板名称', trigger: 'blur' }],
  page_key: [{ required: true, message: '请填写页面键', trigger: 'blur' }],
  page_type: [{ required: true, message: '请选择页面类型', trigger: 'change' }],
}

if (formType === 'edit' && data) {
  model.category_id = data.category_id
  model.name = data.name
  model.page_key = data.page_key
  model.page_type = data.page_type
  model.cover = data.cover || ''
  model.description = data.description || ''
  model.sort = data.sort || 0
  model.is_enabled = data.is_enabled !== false
}

function payload() {
  return {
    ...model,
    cover: model.cover || null,
    description: model.description || null,
    schema: data?.schema || createDefaultSchema(model.page_key, model.name || '装修模板'),
  }
}

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    createDiyTemplate(payload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    updateDiyTemplate(data?.id as number, payload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

defineExpose({
  add,
  edit,
  maForm: {
    getElFormRef: () => elFormRef.value,
  },
})
</script>

<template>
  <el-form ref="elFormRef" :model="model" :rules="rules" label-width="96px">
    <el-form-item label="分类ID" prop="category_id">
      <el-input-number v-model="model.category_id" :min="1" controls-position="right" />
    </el-form-item>
    <el-form-item label="模板名称" prop="name">
      <el-input v-model="model.name" maxlength="100" show-word-limit />
    </el-form-item>
    <el-form-item label="页面键" prop="page_key">
      <el-input v-model="model.page_key" placeholder="home" />
    </el-form-item>
    <el-form-item label="页面类型" prop="page_type">
      <el-radio-group v-model="model.page_type">
        <el-radio-button label="miniprogram">
          小程序
        </el-radio-button>
        <el-radio-button label="h5">
          H5
        </el-radio-button>
        <el-radio-button label="all">
          通用
        </el-radio-button>
      </el-radio-group>
    </el-form-item>
    <el-form-item label="封面">
      <el-input v-model="model.cover" placeholder="图片 URL" />
    </el-form-item>
    <el-form-item label="排序">
      <el-input-number v-model="model.sort" :min="0" controls-position="right" />
    </el-form-item>
    <el-form-item label="启用">
      <el-switch v-model="model.is_enabled" />
    </el-form-item>
    <el-form-item label="说明">
      <el-input v-model="model.description" type="textarea" :rows="3" maxlength="255" show-word-limit />
    </el-form-item>
  </el-form>
</template>
