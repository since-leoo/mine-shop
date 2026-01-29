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
import type { MaFormExpose } from '@mineadmin/form'
import type { CategoryVo } from '~/mall/api/category'
import { create, save } from '~/mall/api/category'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useMessage } from '@/hooks/useMessage.ts'

defineOptions({ name: 'mall:category:form' })

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: CategoryVo | null
}>()

const msg = useMessage()
const formRef = ref<MaFormExpose>()
const model = ref<CategoryVo>({})

useForm('categoryForm').then((form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
  }
  form.setItems(getFormItems(formType, model.value, msg))
  form.setOptions({ labelWidth: '100px' })
})

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    create(model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    save(model.value.id as number, model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

defineExpose({ add, edit, maForm: formRef })
</script>

<template>
  <ma-form ref="categoryForm" v-model="model" />
</template>

<style scoped lang="scss">

</style>
