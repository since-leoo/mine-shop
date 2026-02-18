<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { SeckillOrderSummaryVo } from '~/mall/api/seckill-order'

import { useI18n } from 'vue-i18n'
import { summaryPage, ordersByActivity, seckillOrderExport } from '~/mall/api/seckill-order'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import getOrderColumns from './data/getOrderColumns.tsx'
import { ElMessage } from 'element-plus'

defineOptions({ name: 'mall:seckill_order' })

const { t } = useI18n()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>

const drawerVisible = ref(false)
const drawerTitle = ref('')
const drawerActivityId = ref<number>(0)
const drawerOrders = ref<any[]>([])
const drawerLoading = ref(false)
const drawerPage = ref(1)
const drawerTotal = ref(0)
const drawerPageSize = ref(15)

function onViewOrders(row: SeckillOrderSummaryVo) {
  drawerActivityId.value = row.id!
  drawerTitle.value = t('mall.seckillOrder.drawerTitle', { title: row.title })
  drawerPage.value = 1
  drawerVisible.value = true
  loadDrawerOrders()
}

async function loadDrawerOrders() {
  drawerLoading.value = true
  try {
    const res = await ordersByActivity(drawerActivityId.value, {
      page: drawerPage.value,
      page_size: drawerPageSize.value,
    })
    drawerOrders.value = res.data?.list || []
    drawerTotal.value = res.data?.total || 0
  }
  catch {
    drawerOrders.value = []
    drawerTotal.value = 0
  }
  finally {
    drawerLoading.value = false
  }
}

function onDrawerPageChange(p: number) {
  drawerPage.value = p
  loadDrawerOrders()
}

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 100,
  header: {
    mainTitle: () => t('mall.seckillOrder.title'),
    subTitle: () => t('mall.seckillOrder.subtitle'),
  },
  searchOptions: {
    fold: false,
    text: {
      searchBtn: () => t('mall.search'),
      resetBtn: () => t('mall.reset'),
    },
  },
  searchFormOptions: { labelWidth: '100px' },
  requestOptions: { api: summaryPage },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(onViewOrders),
})

const orderColumns = getOrderColumns()

async function handleExport() {
  try {
    const searchParams = proTableRef.value?.getSearchFormData?.() ?? {}
    const res = await seckillOrderExport(searchParams)
    ElMessage.success(res.message || '导出任务已创建')
  }
  catch (err: any) {
    ElMessage.error(err?.message || '导出失败')
  }
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button plain @click="handleExport">
          <template #icon><ma-svg-icon name="ph:download-simple" size="14" /></template>
          {{ t('mall.export') }}
        </el-button>
      </template>
      <template #empty>
        <el-empty :description="t('mall.seckillOrder.noData')" />
      </template>
    </MaProTable>

    <el-drawer
      v-model="drawerVisible"
      :title="drawerTitle"
      size="80%"
      direction="rtl"
      destroy-on-close
    >
      <el-table
        v-loading="drawerLoading"
        :data="drawerOrders"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column
          v-for="col in orderColumns"
          :key="col.prop as string"
          :label="typeof col.label === 'function' ? col.label() : col.label"
          :prop="col.prop as string"
          :width="col.width"
          :min-width="col.minWidth"
        >
          <template v-if="col.cellRender" #default="scope">
            <component :is="() => col.cellRender!(scope)" />
          </template>
        </el-table-column>
      </el-table>
      <div v-if="drawerTotal > drawerPageSize" class="mt-4 flex justify-end">
        <el-pagination
          :current-page="drawerPage"
          :page-size="drawerPageSize"
          :total="drawerTotal"
          layout="prev, pager, next"
          @current-change="onDrawerPageChange"
        />
      </div>
    </el-drawer>
  </div>
</template>