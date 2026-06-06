<script setup lang="ts">
import type { DiySeckillSelectorVo } from '~/mall/api/diySelector'
import { selectDiySeckills } from '~/mall/api/diySelector'

const visible = defineModel<boolean>('visible', { default: false })

const emit = defineEmits<{
  confirm: [item: DiySeckillSelectorVo]
}>()

const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const pageSize = ref(8)
const total = ref(0)
const list = ref<DiySeckillSelectorVo[]>([])
const selected = ref<DiySeckillSelectorVo | null>(null)

async function load() {
  loading.value = true
  try {
    const res = await selectDiySeckills({
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
  if (!selected.value)
    return
  emit('confirm', selected.value)
  visible.value = false
}

watch(visible, value => value && load())
</script>

<template>
  <el-dialog v-model="visible" title="选择秒杀场次" width="760px">
    <div class="selector-toolbar">
      <el-input v-model="keyword" clearable placeholder="活动名称" @keyup.enter="search" />
      <el-button type="primary" @click="search">查询</el-button>
    </div>
    <el-table
      v-loading="loading"
      :data="list"
      border
      height="390"
      highlight-current-row
      @current-change="selected = $event"
    >
      <el-table-column prop="title" label="活动" min-width="180" />
      <el-table-column prop="start_time" label="开始时间" width="160" />
      <el-table-column prop="end_time" label="结束时间" width="160" />
      <el-table-column prop="status" label="状态" width="90" />
      <el-table-column label="销量" width="100">
        <template #default="{ row }">
          {{ row.sold_quantity }}/{{ row.total_quantity }}
        </template>
      </el-table-column>
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
      <el-button type="primary" :disabled="!selected" @click="confirm">确定</el-button>
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
