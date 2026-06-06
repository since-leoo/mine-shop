<script setup lang="ts">
import type { DiyProductSelectorVo } from '~/mall/api/diySelector'
import { selectDiyProducts } from '~/mall/api/diySelector'

const visible = defineModel<boolean>('visible', { default: false })

const emit = defineEmits<{
  confirm: [items: DiyProductSelectorVo[]]
}>()

const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const pageSize = ref(8)
const total = ref(0)
const list = ref<DiyProductSelectorVo[]>([])
const selected = ref<DiyProductSelectorVo[]>([])

async function load() {
  loading.value = true
  try {
    const res = await selectDiyProducts({
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
  <el-dialog v-model="visible" title="选择商品" width="760px">
    <div class="selector-toolbar">
      <el-input v-model="keyword" clearable placeholder="商品名称" @keyup.enter="search" />
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
      <el-table-column label="商品" min-width="240">
        <template #default="{ row }">
          <div class="selector-product">
            <el-image class="selector-product__image" :src="row.main_image" fit="cover" />
            <span>{{ row.name }}</span>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="价格" width="150">
        <template #default="{ row }">
          ¥{{ row.min_price / 100 }} - ¥{{ row.max_price / 100 }}
        </template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="100" />
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

.selector-product {
  display: flex;
  align-items: center;
  gap: 8px;
}

.selector-product__image {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  background: #f3f4f6;
}

.selector-footer {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}
</style>
