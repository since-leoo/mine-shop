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
import type { BrandVo } from '~/mall/api/brand'
import { create, save } from '~/mall/api/brand'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'mall:brand:form' })

const { t } = useI18n()

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: BrandVo | null
}>()

const formRef = ref<MaFormExpose>()
const model = ref<BrandVo>({})

useForm('brandForm').then((form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
  }
  form.setItems(getFormItems(formType, model.value, t))
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
  <ma-form ref="brandForm" v-model="model" />
</template>

<style scoped lang="scss">

</style>
