<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { FormInstance } from 'element-plus'
import { couponIssue } from '~/mall/api/coupon'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useMessage } from '@/hooks/useMessage.ts'

const props = defineProps<{
  couponId: number
}>()

const formRef = ref<FormInstance>()
const model = ref<{ member_ids: string; expire_at?: string }>({
  member_ids: '',
})
const msg = useMessage()

function reset() {
  model.value.member_ids = ''
  model.value.expire_at = ''
}

async function submit(): Promise<any> {
  await formRef.value?.validate()
  const ids = model.value.member_ids
    .split(',')
    .map(item => Number(item.trim()))
    .filter(Boolean)

  if (ids.length < 1) {
    msg.warning('请输入至少一个会员ID')
    return Promise.reject(new Error('empty-member'))
  }

  const payload: { member_ids: number[]; expire_at?: string } = {
    member_ids: ids,
  }
  if (model.value.expire_at) {
    payload.expire_at = model.value.expire_at
  }

  const res = await couponIssue(props.couponId, payload)
  if (res.code === ResultCode.SUCCESS) {
    reset()
    msg.success('发放成功')
    return res
  }
  throw new Error(res.message || '发放失败')
}

defineExpose({ submit, reset })
</script>

<template>
  <el-form ref="formRef" :model="model" label-width="100px">
    <el-form-item
      label="会员ID列表"
      prop="member_ids"
      :rules="[{ required: true, message: '请输入会员ID' }]"
    >
      <el-input
        v-model="model.member_ids"
        type="textarea"
        rows="3"
        placeholder="输入会员ID，使用逗号分隔"
      />
    </el-form-item>
    <el-form-item label="自定义过期时间" prop="expire_at">
      <el-date-picker
        v-model="model.expire_at"
        type="datetime"
        placeholder="不填则默认到优惠券结束时间"
        value-format="YYYY-MM-DD HH:mm:ss"
        class="w-full"
      />
    </el-form-item>
    <el-alert title="建议通过会员列表导出ID后批量发放，逗号分隔。" type="info" :closable="false" />
  </el-form>
</template>

<style scoped lang="scss">

</style>
