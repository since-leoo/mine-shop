<script setup lang="ts">
import type { DiyPageType, DiyPageVo } from '~/mall/api/diyPage'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'
import {
  copyDiyPage,
  createDiyPage,
  disableDiyPage,
  enableDiyPage,
  pageDiyPages,
  updateDiyPage,
} from '~/mall/api/diyPage'

defineOptions({ name: 'mall:diy:page' })

const router = useRouter()
const loading = ref(false)
const rows = ref<DiyPageVo[]>([])
const total = ref(0)
const query = reactive({
  title: '',
  page_key: '',
  page_type: '' as DiyPageType | '',
  is_enabled: '' as boolean | '',
  page: 1,
  page_size: 15,
})
const dialogVisible = ref(false)
const editing = ref<DiyPageVo | null>(null)
const form = reactive({
  page_key: 'home',
  page_type: 'miniprogram' as DiyPageType,
  title: '',
  description: '',
})

const pageTypeMap: Record<string, string> = {
  miniprogram: '小程序',
  h5: 'H5',
  all: '通用',
}

async function load() {
  loading.value = true
  try {
    const res = await pageDiyPages(query)
    rows.value = res.data?.list || []
    total.value = res.data?.total || 0
  }
  finally {
    loading.value = false
  }
}

function resetQuery() {
  query.title = ''
  query.page_key = ''
  query.page_type = ''
  query.is_enabled = ''
  query.page = 1
  load()
}

function openCreate() {
  editing.value = null
  form.page_key = 'home'
  form.page_type = 'miniprogram'
  form.title = ''
  form.description = ''
  dialogVisible.value = true
}

function openEdit(row: DiyPageVo) {
  editing.value = row
  form.page_key = row.page_key
  form.page_type = row.page_type
  form.title = row.title
  form.description = row.description || ''
  dialogVisible.value = true
}

async function submitForm() {
  if (!form.page_key || !form.title) {
    ElMessage.warning('请填写页面键和页面名称')
    return
  }

  if (editing.value?.id) {
    await updateDiyPage(editing.value.id, form)
    ElMessage.success('页面已更新')
  }
  else {
    await createDiyPage(form)
    ElMessage.success('页面已创建')
  }
  dialogVisible.value = false
  load()
}

async function handleEnable(row: DiyPageVo) {
  if (!row.published_version_id) {
    ElMessage.warning('请先发布页面后再启用')
    return
  }
  await enableDiyPage(row.id!)
  ElMessage.success('页面已启用')
  load()
}

async function handleDisable(row: DiyPageVo) {
  await disableDiyPage(row.id!)
  ElMessage.success('页面已禁用')
  load()
}

async function handleCopy(row: DiyPageVo) {
  await copyDiyPage(row.id!)
  ElMessage.success('页面已复制')
  load()
}

function openEditor(row: DiyPageVo) {
  router.push({ path: '/mall/diy/editor', query: { id: row.id } })
}

onMounted(load)
</script>

<template>
  <div class="mine-layout diy-page-list">
    <div class="diy-page-list__bar">
      <div class="diy-page-list__filters">
        <el-input v-model="query.title" clearable placeholder="页面名称" />
        <el-input v-model="query.page_key" clearable placeholder="页面键" />
        <el-select v-model="query.page_type" clearable placeholder="页面类型">
          <el-option label="小程序" value="miniprogram" />
          <el-option label="H5" value="h5" />
          <el-option label="通用" value="all" />
        </el-select>
        <el-select v-model="query.is_enabled" clearable placeholder="启用状态">
          <el-option label="启用" :value="true" />
          <el-option label="禁用" :value="false" />
        </el-select>
        <el-button type="primary" @click="load"><ma-svg-icon name="ph:magnifying-glass" size="14" />查询</el-button>
        <el-button @click="resetQuery">重置</el-button>
      </div>
      <el-button v-auth="['mall:diy:create']" type="primary" @click="openCreate">
        <ma-svg-icon name="ph:plus" size="14" />新建页面
      </el-button>
    </div>

    <el-table v-loading="loading" :data="rows" row-key="id" border>
      <el-table-column prop="title" label="页面名称" min-width="160" />
      <el-table-column prop="page_key" label="页面键" width="130" />
      <el-table-column label="页面类型" width="110">
        <template #default="{ row }">{{ pageTypeMap[row.page_type] || row.page_type }}</template>
      </el-table-column>
      <el-table-column label="启用" width="90">
        <template #default="{ row }">
          <el-tag :type="row.is_enabled ? 'success' : 'info'">{{ row.is_enabled ? '启用' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="发布" width="100">
        <template #default="{ row }">
          <el-tag :type="row.published_version_id ? 'success' : 'warning'">{{ row.published_version_id ? '已发布' : '未发布' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="updated_at" label="更新时间" width="180" />
      <el-table-column label="操作" width="360" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditor(row)">装修</el-button>
          <el-button link @click="openEdit(row)">编辑</el-button>
          <el-button link @click="handleCopy(row)">复制</el-button>
          <el-button v-if="!row.is_enabled" link type="success" @click="handleEnable(row)">启用</el-button>
          <el-button v-else link type="warning" @click="handleDisable(row)">禁用</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="diy-page-list__pagination">
      <el-pagination
        v-model:current-page="query.page"
        v-model:page-size="query.page_size"
        layout="total, sizes, prev, pager, next"
        :total="total"
        @change="load"
      />
    </div>

    <el-dialog v-model="dialogVisible" :title="editing ? '编辑页面' : '新建页面'" width="520px">
      <el-form label-width="90px">
        <el-form-item label="页面键" required>
          <el-input v-model="form.page_key" placeholder="home" />
        </el-form-item>
        <el-form-item label="页面类型" required>
          <el-radio-group v-model="form.page_type">
            <el-radio-button label="miniprogram">小程序</el-radio-button>
            <el-radio-button label="h5">H5</el-radio-button>
            <el-radio-button label="all">通用</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="页面名称" required>
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="说明">
          <el-input v-model="form.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.diy-page-list {
  padding: 16px;
}

.diy-page-list__bar {
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.diy-page-list__filters {
  display: grid;
  grid-template-columns: 160px 140px 130px 130px auto auto;
  gap: 8px;
}

.diy-page-list__pagination {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}
</style>
