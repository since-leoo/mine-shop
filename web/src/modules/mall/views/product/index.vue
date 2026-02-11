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
import type { UseDrawerExpose } from '@/hooks/useDrawer.ts'

import { page, remove, stats } from '~/mall/api/product'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDrawer from '@/hooks/useDrawer.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import ProductForm from './form.vue'

defineOptions({ name: 'mall:product' })

const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const statsData = ref({
  total: 0,
  active: 0,
  draft: 0,
  inactive: 0,
  sold_out: 0,
  warning_stock: 0,
})
const msg = useMessage()

const maDrawer: UseDrawerExpose = useDrawer({
  size: '100%',
  direction: 'rtl',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success('操作成功') : msg.error(res.message)
        maDrawer.close()
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
    mainTitle: () => '商品管理',
    subTitle: () => '维护商品基础信息与SKU',
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
      isFoldBtn: () => '收起',
      notFoldBtn: () => '展开',
    },
  },
  searchFormOptions: { labelWidth: '90px' },
  requestOptions: { api: page },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDrawer),
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
  msg.delConfirm('确定删除选中的商品吗？').then(async () => {
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
      <el-col :span="4">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-gray-500">商品总数</div>
              <div class="mt-2 text-2xl font-semibold text-gray-800 dark-text-gray-100">{{ statsData.total }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-slate-700 dark-bg-dark-4 dark-text-gray-100">
              <ma-svg-icon name="ph:package" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-emerald-600">上架数</div>
              <div class="mt-2 text-2xl font-semibold text-emerald-700 dark-text-emerald-200">{{ statsData.active }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark-bg-dark-4 dark-text-emerald-200">
              <ma-svg-icon name="ph:check-circle" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-amber-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-amber-600">草稿数</div>
              <div class="mt-2 text-2xl font-semibold text-amber-700 dark-text-amber-200">{{ statsData.draft }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark-bg-dark-4 dark-text-amber-200">
              <ma-svg-icon name="ph:note-pencil" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-gray-500">下架数</div>
              <div class="mt-2 text-2xl font-semibold text-gray-700 dark-text-gray-100">{{ statsData.inactive }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-700 dark-bg-dark-4 dark-text-gray-100">
              <ma-svg-icon name="ph:eye-slash" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-rose-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-rose-600">售罄数</div>
              <div class="mt-2 text-2xl font-semibold text-rose-700 dark-text-rose-200">{{ statsData.sold_out }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark-bg-dark-4 dark-text-rose-200">
              <ma-svg-icon name="ph:warning-circle" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="never" class="border-0">
          <div class="flex items-center justify-between rounded-lg bg-indigo-50 px-4 py-3 dark-bg-dark-7">
            <div>
              <div class="text-xs text-indigo-600">库存预警</div>
              <div class="mt-2 text-2xl font-semibold text-indigo-700 dark-text-indigo-200">{{ statsData.warning_stock }}</div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark-bg-dark-4 dark-text-indigo-200">
              <ma-svg-icon name="ph:alarm" size="18" />
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button
          v-auth="['product:product:create']"
          type="primary"
          @click="() => {
            maDrawer.setTitle('新增商品')
            maDrawer.open({ formType: 'add' })
          }"
        >
          新增商品
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button
          v-auth="['product:product:delete']"
          type="danger"
          plain
          :disabled="selections.length < 1"
          @click="handleBatchDelete"
        >
          批量删除
        </el-button>
      </template>
      <template #empty>
        <el-empty>
          <el-button
            v-auth="['product:product:create']"
            type="primary"
            @click="() => {
              maDrawer.setTitle('新增商品')
              maDrawer.open({ formType: 'add' })
            }"
          >
            新增商品
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="maDrawer.Drawer">
      <template #default="{ formType, data }">
        <ProductForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>
