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

import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { activityPage, activityRemove, activityStats, activityExport } from '~/mall/api/seckill'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import ActivityForm from './form.vue'

defineOptions({ name: 'mall:seckill' })

const { t } = useI18n()
const router = useRouter()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const statsData = ref({ total: 0, enabled: 0, disabled: 0 })
const msg = useMessage()

const maDialog: UseDialogExpose = useDialog({
  lgWidth: '600px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success(t('mall.seckill.operationSuccess')) : msg.error(res.message)
        maDialog.close()
        proTableRef.value.refresh()
        loadStats()
      }).catch((err: any) => {
        msg.alertError(err)
      }).finally(() => okLoadingState(false))
    }).catch(() => okLoadingState(false))
  },
})

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 161,
  header: {
    mainTitle: () => t('mall.seckill.title'),
    subTitle: () => t('mall.seckill.subtitle'),
  },
  tableOptions: {
    on: {
      onSelectionChange: (selection: any[]) => selections.value = selection,
    },
  },
  searchOptions: {
    fold: true,
    text: {
      searchBtn: () => t('mall.search'),
      resetBtn: () => t('mall.reset'),
      isFoldBtn: () => t('mall.common.unfold'),
      notFoldBtn: () => t('mall.common.fold'),
    },
  },
  searchFormOptions: { labelWidth: '100px' },
  requestOptions: { api: activityPage },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDialog, router, proTableRef),
})

async function loadStats() {
  try {
    const res = await activityStats()
    statsData.value = res.data
  }
  catch (err: any) {
    msg.alertError(err)
  }
}

function handleBatchDelete() {
  const ids = selections.value.map(item => item.id)
  if (ids.length < 1) {
    msg.warning(t('mall.seckill.selectDeleteData'))
    return
  }
  msg.delConfirm(t('mall.seckill.confirmDeleteActivity')).then(async () => {
    const tasks = ids.map((id: number) => activityRemove(id))
    const results = await Promise.all(tasks)
    if (results.every(item => item.code === ResultCode.SUCCESS)) {
      msg.success(t('mall.seckill.deleteSuccess'))
      proTableRef.value.refresh()
      loadStats()
    }
  })
}

async function handleExport() {
  try {
    const searchParams = proTableRef.value?.getSearchFormData?.() ?? {}
    const res = await activityExport(searchParams)
    msg.success(res.message || '导出任务已创建')
  }
  catch (err: any) {
    msg.alertError(err)
  }
}

onMounted(() => {
  loadStats()
})
</script>

<template>
  <div class="mine-layout pt-3">
    <el-row :gutter="12" class="mb-3 px-3">
      <el-col :span="8">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-gray-500">{{ t('mall.seckill.totalActivities') }}</div>
              <div class="mt-2 text-2xl font-semibold text-gray-800 dark-text-gray-100">{{ statsData.total }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-slate-700 dark-bg-dark-4 dark-text-gray-100">
              <ma-svg-icon name="ph:lightning" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-emerald-600">{{ t('mall.seckill.enabledCount') }}</div>
              <div class="mt-2 text-2xl font-semibold text-emerald-700 dark-text-emerald-200">{{ statsData.enabled }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark-bg-dark-4 dark-text-emerald-200">
              <ma-svg-icon name="ph:check-circle" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-gray-500">{{ t('mall.seckill.disabledCount') }}</div>
              <div class="mt-2 text-2xl font-semibold text-gray-700 dark-text-gray-100">{{ statsData.disabled }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-700 dark-bg-dark-4 dark-text-gray-100">
              <ma-svg-icon name="ph:x-circle" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button
          v-auth="['seckill:activity:create']"
          type="primary"
          @click="() => {
            maDialog.setTitle(t('mall.seckill.addActivity'))
            maDialog.open({ formType: 'add' })
          }"
        >
          {{ t('mall.seckill.addActivity') }}
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button
          v-auth="['seckill:activity:delete']"
          type="danger"
          plain
          :disabled="selections.length < 1"
          @click="handleBatchDelete"
        >
          {{ t('mall.seckill.batchDelete') }}
        </el-button>
        <el-button plain @click="handleExport">
          <template #icon><ma-svg-icon name="ph:download-simple" size="14" /></template>
          {{ t('mall.export') }}
        </el-button>
      </template>
      <template #empty>
        <el-empty :description="t('mall.seckill.noActivities')">
          <el-button
            v-auth="['seckill:activity:create']"
            type="primary"
            @click="() => {
              maDialog.setTitle(t('mall.seckill.addActivity'))
              maDialog.open({ formType: 'add' })
            }"
          >
            {{ t('mall.seckill.addActivity') }}
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, data }">
        <ActivityForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>