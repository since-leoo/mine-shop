<script setup lang="ts">
import type { DiyPageType, DiyPageVo, DiyPublishRecordVo } from '~/mall/api/diyPage'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'
import {
  copyDiyPage,
  createDiyPage,
  createDiyPreviewToken,
  disableDiyPage,
  enableDiyPage,
  getDiyPublishRecords,
  pageDiyPages,
  rollbackDiyPage,
  saveDiyPageAsTemplate,
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
  status: '',
  page: 1,
  page_size: 15,
})
const dialogVisible = ref(false)
const templateVisible = ref(false)
const recordVisible = ref(false)
const editing = ref<DiyPageVo | null>(null)
const templateSource = ref<DiyPageVo | null>(null)
const recordSource = ref<DiyPageVo | null>(null)
const publishRecords = ref<DiyPublishRecordVo[]>([])
const recordLoading = ref(false)
const form = reactive({
  page_key: 'home',
  page_type: 'miniprogram' as DiyPageType,
  title: '',
  description: '',
})
const templateForm = reactive({
  category_id: 1,
  name: '',
  cover: '',
  description: '',
  sort: 0,
  is_enabled: true,
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
  query.status = ''
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

function openSaveAsTemplate(row: DiyPageVo) {
  if (!row.published_version) {
    ElMessage.warning('请先发布页面后再保存为模板')
    return
  }
  templateSource.value = row
  templateForm.category_id = 1
  templateForm.name = `${row.title}模板`
  templateForm.cover = ''
  templateForm.description = row.description || ''
  templateForm.sort = 0
  templateForm.is_enabled = true
  templateVisible.value = true
}

async function submitSaveAsTemplate() {
  const row = templateSource.value
  if (!row?.id || !row.published_version?.schema) {
    ElMessage.warning('缺少已发布页面结构')
    return
  }
  if (!templateForm.name) {
    ElMessage.warning('请填写模板名称')
    return
  }

  await saveDiyPageAsTemplate(row.id, {
    category_id: templateForm.category_id,
    name: templateForm.name,
    page_key: row.page_key,
    page_type: row.page_type,
    cover: templateForm.cover || null,
    description: templateForm.description || null,
    schema: row.published_version.schema,
    sort: templateForm.sort,
    is_enabled: templateForm.is_enabled,
  })
  ElMessage.success('已保存为模板')
  templateVisible.value = false
}

async function openPublishRecords(row: DiyPageVo) {
  recordSource.value = row
  recordVisible.value = true
  recordLoading.value = true
  try {
    const res = await getDiyPublishRecords(row.id!)
    publishRecords.value = res.data || []
  }
  finally {
    recordLoading.value = false
  }
}

async function handleRollback(versionId: number) {
  const pageId = recordSource.value?.id
  if (!pageId)
    return
  await ElMessageBox.confirm('确认回滚到该历史发布版本？当前线上版本会被替换。', '回滚页面', {
    type: 'warning',
  })
  await rollbackDiyPage(pageId, versionId)
  ElMessage.success('页面已回滚')
  recordVisible.value = false
  load()
}

async function handlePreview(row: DiyPageVo) {
  if (!row.published_version_id) {
    ElMessage.warning('请先发布页面后再预览')
    return
  }
  const res = await createDiyPreviewToken(row.id!, row.published_version_id)
  const token = res.data?.token
  if (!token) {
    ElMessage.warning('预览令牌生成失败')
    return
  }
  ElMessage.success(`预览令牌：${token}`)
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
        <el-select v-model="query.status" clearable placeholder="发布状态">
          <el-option label="草稿" value="draft" />
          <el-option label="已发布" value="published" />
          <el-option label="已禁用" value="disabled" />
        </el-select>
        <el-button type="primary" @click="load"><ma-svg-icon name="ph:magnifying-glass" size="14" />查询</el-button>
        <el-button @click="resetQuery">重置</el-button>
      </div>
      <el-button v-auth="['mall:diy:page:create']" type="primary" @click="openCreate">
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
          <el-button link @click="openPublishRecords(row)">记录</el-button>
          <el-button link @click="handlePreview(row)">预览</el-button>
          <el-button link @click="openSaveAsTemplate(row)">存模板</el-button>
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

    <el-dialog v-model="templateVisible" title="保存为模板" width="520px">
      <el-form label-width="96px">
        <el-form-item label="来源页面">
          <span>{{ templateSource?.title }}</span>
        </el-form-item>
        <el-form-item label="分类ID" required>
          <el-input-number v-model="templateForm.category_id" :min="1" controls-position="right" />
        </el-form-item>
        <el-form-item label="模板名称" required>
          <el-input v-model="templateForm.name" />
        </el-form-item>
        <el-form-item label="封面">
          <el-input v-model="templateForm.cover" placeholder="图片 URL" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="templateForm.sort" :min="0" controls-position="right" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="templateForm.is_enabled" />
        </el-form-item>
        <el-form-item label="说明">
          <el-input v-model="templateForm.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="templateVisible = false">取消</el-button>
        <el-button type="primary" @click="submitSaveAsTemplate">保存</el-button>
      </template>
    </el-dialog>

    <el-drawer v-model="recordVisible" title="发布记录" size="620px">
      <el-table v-loading="recordLoading" :data="publishRecords" border>
        <el-table-column prop="publish_type" label="类型" width="100" />
        <el-table-column prop="publish_status" label="状态" width="100" />
        <el-table-column prop="version_id" label="版本" width="90" />
        <el-table-column prop="published_at" label="发布时间" min-width="160" />
        <el-table-column prop="scheduled_at" label="定时时间" min-width="160" />
        <el-table-column label="操作" width="90" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.version_id && row.publish_status === 'published'"
              link
              type="primary"
              @click="handleRollback(row.version_id)"
            >
              回滚
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-drawer>
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
  grid-template-columns: 150px 130px 120px 120px 120px auto auto;
  gap: 8px;
}

.diy-page-list__pagination {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}
</style>
