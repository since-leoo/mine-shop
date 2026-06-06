<script setup lang="ts">
import type { DiyCouponSelectorVo } from '~/mall/api/diySelector'
import { selectDiyCoupons } from '~/mall/api/diySelector'

const visible = defineModel<boolean>('visible', { default: false })

const emit = defineEmits<{
  confirm: [items: DiyCouponSelectorVo[]]
}>()

const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const pageSize = ref(8)
const total = ref(0)
const list = ref<DiyCouponSelectorVo[]>([])
const selected = ref<DiyCouponSelectorVo[]>([])

async function load() {
  loading.value = true
  try {
    const res = await selectDiyCoupons({
      keyword: keyword.value,
      page: page.value,
      page_size: pageSize.value,
    })
    list.value = res.data?.list || []
    total.value = res.data?.total || 0
  }
  finally {
    loading.value = false
  }
}

function search() {
  page.value = 1
  load()
}

function confirm() {
  emit('confirm', selected.value)
  visible.value = false
}

watch(visible, value => value && load())
</script>

<template>
  <el-dialog v-model="visible" title="选择优惠券" width="720px">
    <div class="selector-toolbar">
      <el-input v-model="keyword" clearable placeholder="优惠券名称" @keyup.enter="search" />
      <el-button type="primary" @click="search">查询</el-button>
    </div>
    <el-table
      v-loading="loading"
      :data="list"
      border
      height="390"
      @selection-change="selected = $event"
    >
      <el-table-column type="selection" width="48" />
      <el-table-column prop="name" label="名称" min-width="180" />
      <el-table-column prop="type" label="类型" width="100" />
      <el-table-column label="面额" width="100">
        <template #default="{ row }">
          ¥{{ row.value / 100 }}
        </template>
      </el-table-column>
      <el-table-column label="门槛" width="110">
        <template #default="{ row }">
          ¥{{ row.min_amount / 100 }}
        </template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="90" />
    </el-table>
    <div class="selector-footer">
      <el-pagination
        v-model:current-page="page"
        v-model:page-size="pageSize"
        layout="prev, pager, next, total"
        :total="total"
        @current-change="load"
      />
    </div>
    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" @click="confirm">确定</el-button>
    </template>
  </el-dialog>
</template>

<style scoped lang="scss">
.selector-toolbar {
  margin-bottom: 12px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
}

.selector-footer {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}
</style>
