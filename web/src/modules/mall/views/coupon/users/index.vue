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
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'mall:coupon:user-list' })

const route = useRoute()
const router = useRouter()
const msg = useMessage()
const { t } = useI18n()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const couponId = computed(() => Number(route.query.coupon_id ?? 0))

const statusMap: Record<string, { text: string; type: any }> = {
  unused: { text: t('mall.couponUser.unused'), type: 'info' },
  used: { text: t('mall.couponUser.used'), type: 'success' },
  expired: { text: t('mall.couponUser.expired'), type: 'warning' },
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
    mainTitle: () => t('mall.couponUser.recordTitle'),
    subTitle: () => t('mall.couponUser.recordSubtitle'),
    backBtn: {
      show: true,
      onClick: () => router.back(),
    },
  },
})

const schema = ref<MaProTableSchema>({
  searchItems: [
    {
      label: () => t('mall.couponUser.keywordLabel'),
      prop: 'keyword',
      render: 'input',
      renderProps: { placeholder: t('mall.couponUser.keywordPlaceholder') },
    },
    {
      label: () => t('mall.couponUser.statusColumn'),
      prop: 'status',
      render: () => (
        <el-select placeholder={t('mall.couponUser.statusPlaceholder')}>
          <el-option label={t('mall.couponUser.unused')} value="unused" />
          <el-option label={t('mall.couponUser.used')} value="used" />
          <el-option label={t('mall.couponUser.expired')} value="expired" />
        </el-select>
      ),
    },
  ],
  tableColumns: [
    { label: () => t('mall.couponUser.memberColumn'), prop: 'member_nickname', minWidth: '160px',
      cellRender: ({ row }: { row: CouponUserVo }) => (
        <div>
          <div class="font-medium">{row.member_nickname ?? '--'}</div>
          <div class="text-xs text-gray-500">{row.member_phone ?? '--'}</div>
        </div>
      ),
    },
    { label: () => t('mall.couponUser.couponColumn'), prop: 'coupon_name', minWidth: '160px' },
    { label: () => t('mall.couponUser.statusColumn'), prop: 'status', width: '120px',
      cellRender: ({ row }: { row: CouponUserVo }) => {
        const meta = statusMap[row.status || 'unused']
        return <ElTag type={meta?.type}>{meta?.text}</ElTag>
      },
    },
    { label: () => t('mall.couponUser.receivedAt'), prop: 'received_at', width: '160px' },
    { label: () => t('mall.couponUser.usedAt'), prop: 'used_at', width: '160px' },
    { label: () => t('mall.couponUser.expireAt'), prop: 'expire_at', width: '160px' },
    {
      type: 'operation',
      label: () => t('mall.order.operation'),
      width: '220px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'mark-used',
            show: ({ row }: { row: CouponUserVo }) => row.status === 'unused' && hasAuth('coupon:user:update'),
            text: () => t('mall.couponUser.markUsed'),
            icon: 'ph:check-circle',
            onClick: async ({ row }: { row: CouponUserVo }) => {
              const res = await couponUserMarkUsed(row.id as number)
              if (res.code === ResultCode.SUCCESS) {
                msg.success(t('mall.operationSuccess'))
                await proTableRef.value.refresh()
              }
            },
          },
          {
            name: 'mark-expired',
            show: ({ row }: { row: CouponUserVo }) => row.status === 'unused' && hasAuth('coupon:user:update'),
            text: () => t('mall.couponUser.markExpired'),
            icon: 'ph:warning',
            onClick: async ({ row }: { row: CouponUserVo }) => {
              const res = await couponUserMarkExpired(row.id as number)
              if (res.code === ResultCode.SUCCESS) {
                msg.success(t('mall.operationSuccess'))
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
