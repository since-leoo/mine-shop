<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { CouponUserVo } from '~/mall/api/coupon'

import { couponUserMarkExpired, couponUserMarkUsed, couponUserPage } from '~/mall/api/coupon'
import { useRoute, useRouter } from 'vue-router'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { ElTag } from 'element-plus'

defineOptions({ name: 'mall:coupon:user-list' })

const route = useRoute()
const router = useRouter()
const msg = useMessage()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const couponId = computed(() => Number(route.query.coupon_id ?? 0))

const statusMap: Record<string, { text: string; type: any }> = {
  unused: { text: '未使用', type: 'info' },
  used: { text: '已使用', type: 'success' },
  expired: { text: '已过期', type: 'warning' },
}

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 110,
  requestOptions: {
    api: couponUserPage,
    params: () => ({
      coupon_id: couponId.value || undefined,
    }),
  },
  header: {
    mainTitle: () => '领券记录',
    subTitle: () => '查看用户领取与使用情况',
    backBtn: {
      show: true,
      onClick: () => router.back(),
    },
  },
})

const schema = ref<MaProTableSchema>({
  searchItems: [
    {
      label: () => '关键字',
      prop: 'keyword',
      render: 'input',
      renderProps: { placeholder: '输入昵称或手机号' },
    },
    {
      label: () => '状态',
      prop: 'status',
      render: 'select',
      renderProps: { placeholder: '请选择' },
      renderSlots: {
        default: () => [
          <el-option label="未使用" value="unused" />,
          <el-option label="已使用" value="used" />,
          <el-option label="已过期" value="expired" />,
        ],
      },
    },
  ],
  tableColumns: [
    { label: () => '会员', prop: 'member_nickname', minWidth: '160px',
      cellRender: ({ row }: { row: CouponUserVo }) => (
        <div>
          <div class="font-medium">{row.member_nickname ?? '--'}</div>
          <div class="text-xs text-gray-500">{row.member_phone ?? '--'}</div>
        </div>
      ),
    },
    { label: () => '优惠券', prop: 'coupon_name', minWidth: '160px' },
    { label: () => '状态', prop: 'status', width: '120px',
      cellRender: ({ row }: { row: CouponUserVo }) => {
        const meta = statusMap[row.status || 'unused']
        return <ElTag type={meta?.type}>{meta?.text}</ElTag>
      },
    },
    { label: () => '领取时间', prop: 'received_at', width: '160px' },
    { label: () => '使用时间', prop: 'used_at', width: '160px' },
    { label: () => '过期时间', prop: 'expire_at', width: '160px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '220px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'mark-used',
            show: ({ row }: { row: CouponUserVo }) => row.status === 'unused' && hasAuth('coupon:user:update'),
            text: () => '标记已使用',
            icon: 'ph:check-circle',
            onClick: async ({ row }: { row: CouponUserVo }) => {
              const res = await couponUserMarkUsed(row.id as number)
              if (res.code === ResultCode.SUCCESS) {
                msg.success('操作成功')
                await proTableRef.value.refresh()
              }
            },
          },
          {
            name: 'mark-expired',
            show: ({ row }: { row: CouponUserVo }) => row.status === 'unused' && hasAuth('coupon:user:update'),
            text: () => '标记过期',
            icon: 'ph:warning',
            onClick: async ({ row }: { row: CouponUserVo }) => {
              const res = await couponUserMarkExpired(row.id as number)
              if (res.code === ResultCode.SUCCESS) {
                msg.success('操作成功')
                await proTableRef.value.refresh()
              }
            },
          },
        ],
      },
    },
  ],
})
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema" />
  </div>
</template>

<style scoped lang="scss">

</style>
