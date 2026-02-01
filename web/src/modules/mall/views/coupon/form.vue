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
import type { CouponVo } from '~/mall/api/coupon'
import { couponCreate, couponUpdate } from '~/mall/api/coupon'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'mall:coupon:form' })

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: CouponVo | null
}>()

const formRef = ref<MaFormExpose>()
const model = ref<CouponVo>({})

useForm('couponForm').then(async (form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
    model.value.dateRange = [data.start_time, data.end_time]
  }
  else {
    model.value.status = 'active'
  }

  form.setItems(getFormItems(model.value))
  form.setOptions({ labelWidth: '100px' })
})

function normalizeRange() {
  if (Array.isArray(model.value.dateRange)) {
    model.value.start_time = model.value.dateRange[0]
    model.value.end_time = model.value.dateRange[1]
  }
}

function add(): Promise<any> {
  normalizeRange()
  return new Promise((resolve, reject) => {
    couponCreate(model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  normalizeRange()
  return new Promise((resolve, reject) => {
    couponUpdate(model.value.id as number, model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

defineExpose({ add, edit, maForm: formRef })
</script>

<template>
  <ma-form ref="couponForm" v-model="model" />
</template>

<style scoped lang="scss">

</style>
