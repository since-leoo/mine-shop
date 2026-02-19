<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { MaFormExpose } from '@mineadmin/form'
import type { SeckillActivityVo } from '~/mall/api/seckill'
import { activityCreate, activityUpdate } from '~/mall/api/seckill'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'mall:seckill:form' })

const { t } = useI18n()

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: SeckillActivityVo | null
}>()

const formRef = ref<MaFormExpose>()
const model = ref<SeckillActivityVo>({})

useForm('activityForm').then(async (form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
  }
  form.setItems(getFormItems(formType, model.value, t))
  form.setOptions({ labelWidth: '90px' })
})

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    activityCreate(model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    activityUpdate(model.value.id as number, model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

defineExpose({ add, edit, maForm: formRef })
</script>

<template>
  <ma-form ref="activityForm" v-model="model" />
</template>

<style scoped lang="scss">

</style>
