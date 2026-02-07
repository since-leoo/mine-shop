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
import { centsToYuan, yuanToCents } from '@/utils/price'

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
    // API返回分，表单显示元（fixed类型的value和min_amount）
    // percent类型的value存储为850表示8.5折，表单显示8.5
    if (data.type === 'percent') {
      model.value.value = (Number(data.value ?? 0) / 100) as any
    }
    else {
      model.value.value = centsToYuan(data.value) as any
    }
    model.value.min_amount = centsToYuan(data.min_amount) as any
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

function buildCouponPayload(): CouponVo {
  const payload = { ...model.value }
  // 表单中金额为元，提交时转换为分
  // percent类型的value: 表单8.5 → 存储850
  if (payload.type === 'percent') {
    payload.value = Math.round(Number(payload.value ?? 0) * 100) as any
  }
  else {
    payload.value = yuanToCents(payload.value) as any
  }
  payload.min_amount = yuanToCents(payload.min_amount) as any
  return payload
}

function add(): Promise<any> {
  normalizeRange()
  return new Promise((resolve, reject) => {
    couponCreate(buildCouponPayload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  normalizeRange()
  return new Promise((resolve, reject) => {
    couponUpdate(model.value.id as number, buildCouponPayload()).then((res: any) => {
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
