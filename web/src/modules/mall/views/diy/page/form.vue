<script setup lang="ts">
import type { DiyPageType, DiyPageVo } from '~/mall/api/diyPage'
import { createDiyPage, updateDiyPage } from '~/mall/api/diyPage'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'mall:diy:page:form' })

const { formType = 'add', data = null } = defineProps<{
  formType?: 'add' | 'edit'
  data?: DiyPageVo | null
}>()

const elFormRef = ref()
const model = reactive({
  page_key: 'home',
  page_type: 'miniprogram' as DiyPageType,
  title: '',
  description: '',
})

const rules = {
  page_key: [{ required: true, message: '请填写页面键', trigger: 'blur' }],
  page_type: [{ required: true, message: '请选择页面类型', trigger: 'change' }],
  title: [{ required: true, message: '请填写页面名称', trigger: 'blur' }],
}

if (formType === 'edit' && data) {
  model.page_key = data.page_key
  model.page_type = data.page_type
  model.title = data.title
  model.description = data.description || ''
}

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    createDiyPage(model).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    updateDiyPage(data?.id as number, model).then((res: any) => {
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
    <el-form-item label="页面名称" prop="title">
      <el-input v-model="model.title" maxlength="100" show-word-limit />
    </el-form-item>
    <el-form-item label="说明">
      <el-input v-model="model.description" type="textarea" :rows="3" maxlength="255" show-word-limit />
    </el-form-item>
  </el-form>
</template>
