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
import type { UseDialogExpose } from '@/hooks/useDialog.ts'
import type { CouponVo } from '~/mall/api/coupon'

import { couponPage, couponStats } from '~/mall/api/coupon'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import IssueDialog from './issue-dialog.vue'
import CouponForm from './form.vue'
import { useRouter } from 'vue-router'

defineOptions({ name: 'mall:coupon' })

const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref<any>()
const issueRef = ref<any>()
const statsData = ref({ total: 0, active: 0, inactive: 0 })
const msg = useMessage()
const router = useRouter()

const formDialog: UseDialogExpose = useDialog({
  lgWidth: '620px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success('操作成功') : msg.error(res.message)
        formDialog.close()
        proTableRef.value.refresh()
        loadStats()
      }).catch((err: any) => {
        msg.alertError(err)
      }).finally(() => okLoadingState(false))
    }).catch(() => okLoadingState(false))
  },
})

const issueDialog: UseDialogExpose = useDialog({
  lgWidth: '520px',
  ok: (_, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    issueRef.value.submit().then(() => {
      issueDialog.close()
      proTableRef.value.refresh()
      loadStats()
    }).catch((err: any) => {
      msg.alertError(err)
    }).finally(() => okLoadingState(false))
  },
})

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 140,
  header: {
    mainTitle: () => '优惠券管理',
    subTitle: () => '配置并发放营销优惠券',
  },
  searchOptions: {
    fold: true,
  },
  searchFormOptions: { labelWidth: '90px' },
  requestOptions: { api: couponPage },
})

const openIssue = (row: CouponVo) => {
  issueDialog.setTitle(`发放 ${row.name}`)
  issueDialog.open({ couponId: row.id })
}

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(formDialog, router, proTableRef, openIssue),
})

async function loadStats() {
  try {
    const res = await couponStats()
    statsData.value = res.data
  }
  catch (err: any) {
    msg.alertError(err)
  }
}

function handleAdd() {
  formDialog.setTitle('新增优惠券')
  formDialog.open({ formType: 'add', data: {} })
}

onMounted(() => {
  loadStats()
})
</script>

<template>
  <div class="mine-layout pt-3">
    <el-row :gutter="12" class="mb-3">
      <el-col :span="8">
        <el-card shadow="never" class="border-0">
          <div class="stats-card slate">
            <div>
              <div class="stats-label">优惠券总数</div>
              <div class="stats-value">{{ statsData.total }}</div>
            </div>
            <ma-svg-icon name="ph:ticket" size="24" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never" class="border-0">
          <div class="stats-card emerald">
            <div>
              <div class="stats-label">启用中</div>
              <div class="stats-value">{{ statsData.active }}</div>
            </div>
            <ma-svg-icon name="ph:check-circle" size="24" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never" class="border-0">
          <div class="stats-card gray">
            <div>
              <div class="stats-label">停用</div>
              <div class="stats-value">{{ statsData.inactive }}</div>
            </div>
            <ma-svg-icon name="ph:pause-circle" size="24" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button v-auth="['coupon:create']" type="primary" @click="handleAdd">
          新增优惠券
        </el-button>
      </template>
    </MaProTable>

    <component :is="formDialog.Dialog">
      <template #default="{ formType, data }">
        <CouponForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>

    <component :is="issueDialog.Dialog">
      <template #default="{ couponId }">
        <IssueDialog ref="issueRef" :coupon-id="couponId" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">
.stats-card {
  --stats-card-surface: var(--el-bg-color);
  --stats-card-bg: color-mix(in srgb, var(--stats-card-surface) 92%, transparent);
  --stats-card-color: var(--el-text-color-primary);
  @apply flex items-center justify-between rounded-xl px-4 py-3;
  background: var(--stats-card-bg);
  color: var(--stats-card-color);
  border: 1px solid color-mix(in srgb, var(--el-border-color) 60%, transparent);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  transition: background 0.2s ease, color 0.2s ease, border 0.2s ease, box-shadow 0.2s ease;

  &.slate {
    --stats-card-bg: color-mix(in srgb, #1e293b 18%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-text-color-primary) 70%, #0f172a 30%);
  }

  &.emerald {
    --stats-card-bg: color-mix(in srgb, #10b981 20%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, #065f46 55%, var(--el-text-color-primary) 45%);
  }

  &.gray {
    --stats-card-bg: color-mix(in srgb, #6b7280 18%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, #374151 60%, var(--el-text-color-primary) 40%);
  }

  .stats-label {
    font-size: 12px;
    color: color-mix(in srgb, var(--stats-card-color) 55%, var(--el-text-color-secondary) 45%);
  }

  .stats-value {
    margin-top: 8px;
    font-size: 26px;
    font-weight: 600;
  }
}

:global(html.dark) {
  .stats-card {
    --stats-card-surface: var(--el-bg-color-overlay);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
  }

  .stats-card.slate {
    --stats-card-bg: color-mix(in srgb, #1e293b 55%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 85%, #1e293b 15%);
  }

  .stats-card.emerald {
    --stats-card-bg: color-mix(in srgb, #10b981 45%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 80%, #d1fae5 20%);
  }

  .stats-card.gray {
    --stats-card-bg: color-mix(in srgb, #6b7280 42%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 80%, #e5e7eb 20%);
  }

  .stats-card .stats-label {
    color: color-mix(in srgb, var(--el-color-white) 75%, transparent);
  }
}
</style>
