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

import { page, remove, stats } from '~/mall/api/group-buy'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import GroupBuyForm from './form.vue'

defineOptions({ name: 'mall:group_buy' })

const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const statsData = ref({ total: 0, enabled: 0, disabled: 0, active: 0 })
const msg = useMessage()

const maDialog: UseDialogExpose = useDialog({
  lgWidth: '720px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success('操作成功') : msg.error(res.message)
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
    mainTitle: () => '团购管理',
    subTitle: () => '维护团购活动与库存',
  },
  tableOptions: {
    on: {
      onSelectionChange: (selection: any[]) => selections.value = selection,
    },
  },
  searchOptions: {
    fold: true,
    text: {
      searchBtn: () => '搜索',
      resetBtn: () => '重置',
      isFoldBtn: () => '展开',
      notFoldBtn: () => '收起',
    },
  },
  searchFormOptions: { labelWidth: '90px' },
  requestOptions: { api: page },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDialog, proTableRef),
})

async function loadStats() {
  try {
    const res = await stats()
    statsData.value = res.data
  }
  catch (err: any) {
    msg.alertError(err)
  }
}

function handleBatchDelete() {
  const ids = selections.value.map(item => item.id)
  if (ids.length < 1) {
    msg.warning('请选择要删除的数据')
    return
  }
  msg.delConfirm('确定删除选中的团购活动吗？').then(async () => {
    const tasks = ids.map((id: number) => remove(id))
    const results = await Promise.all(tasks)
    if (results.every(item => item.code === ResultCode.SUCCESS)) {
      msg.success('删除成功')
      proTableRef.value.refresh()
      loadStats()
    }
  })
}

onMounted(() => {
  loadStats()
})
</script>

<template>
  <div class="mine-layout pt-3">
    <el-row :gutter="12" class="mb-3 px-3">
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-gray-500">活动总数</div>
              <div class="mt-2 text-2xl font-semibold text-gray-800 dark-text-gray-100">{{ statsData.total }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-slate-700 dark-bg-dark-4 dark-text-gray-100">
              <ma-svg-icon name="ph:users-three" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-emerald-600">已启用</div>
              <div class="mt-2 text-2xl font-semibold text-emerald-700 dark-text-emerald-200">{{ statsData.enabled }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark-bg-dark-4 dark-text-emerald-200">
              <ma-svg-icon name="ph:check-circle" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-blue-600">进行中</div>
              <div class="mt-2 text-2xl font-semibold text-blue-700 dark-text-blue-200">{{ statsData.active }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark-bg-dark-4 dark-text-blue-200">
              <ma-svg-icon name="ph:play-circle" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-gray-500">已禁用</div>
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
          v-auth="['promotion:group_buy:create']"
          type="primary"
          @click="() => {
            maDialog.setTitle('新增团购')
            maDialog.open({ formType: 'add' })
          }"
        >
          新增团购
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button
          v-auth="['promotion:group_buy:delete']"
          type="danger"
          plain
          :disabled="selections.length < 1"
          @click="handleBatchDelete"
        >
          批量删除
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无团购活动">
          <el-button
            v-auth="['promotion:group_buy:create']"
            type="primary"
            @click="() => {
              maDialog.setTitle('新增团购')
              maDialog.open({ formType: 'add' })
            }"
          >
            新增团购
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, data }">
        <GroupBuyForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>
