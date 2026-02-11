<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { SeckillOrderSummaryVo } from '~/mall/api/seckill-order'

import { summaryPage, ordersByActivity } from '~/mall/api/seckill-order'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import getOrderColumns from './data/getOrderColumns.tsx'

defineOptions({ name: 'mall:seckill_order' })

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
  drawerTitle.value = `秒杀订单 - ${row.title}`
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
    mainTitle: () => '秒杀订单',
    subTitle: () => '以秒杀活动为维度查看秒杀订单情况',
  },
  searchOptions: {
    fold: false,
    text: {
      searchBtn: () => '搜索',
      resetBtn: () => '重置',
    },
  },
  searchFormOptions: { labelWidth: '90px' },
  requestOptions: { api: summaryPage },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(onViewOrders),
})

const orderColumns = getOrderColumns()
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #empty>
        <el-empty description="暂无秒杀订单数据" />
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
