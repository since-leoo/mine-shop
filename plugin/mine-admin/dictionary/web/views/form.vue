<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { DictionaryVo } from '../api/Dictionary.ts'
import type { DictionaryTypeVo } from '../api/DictionaryType.ts'
import { create, save } from '../api/Dictionary.ts'
import getFormItems from './components/GetFormItems.tsx'
import type { MaFormExpose } from '@mineadmin/form'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

const { formType = 'add', typeInfo = null, data = null } = defineProps<{
  formType: 'add' | 'edit'
  typeInfo?: DictionaryTypeVo | null
  data?: DictionaryVo | null
}>()

const t = useTrans().globalTrans
const maFormRef = ref<MaFormExpose>()
const formModel = ref<DictionaryVo>(<DictionaryVo>{})

defineOptions({ name: 'dataCenter:dictionary:form' })

useForm('maFormRef').then((form: MaFormExpose) => {
  if (typeInfo) {
    formModel.value!.type_id = typeInfo.id as number
    formModel.value.type_name = typeInfo.name
  }
  if (formType === 'add') {
    formModel.value.status = 1
    formModel.value.i18n_scope = 1
    formModel.value.sort = 0
  }
  if (formType === 'edit' && data) {
    Object.keys(data).map((key: string) => {
      formModel.value[key] = data[key]
    })
  }
  form.setItems(getFormItems(formType, t, formModel.value))
  form.setOptions({
    labelWidth: '100px',
  })
})

// 创建操作
function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    create(formModel.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch((err) => {
      reject(err)
    })
  })
}

// 更新操作
function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    save(formModel.value.id as number, formModel.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch((err) => {
      reject(err)
    })
  })
}

defineExpose({
  add,
  edit,
  maForm: maFormRef,
})
</script>

<template>
  <ma-form ref="maFormRef" v-model="formModel" />
</template>

<style scoped lang="scss">

</style>
