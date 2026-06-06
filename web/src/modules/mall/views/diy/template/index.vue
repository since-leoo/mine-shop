<script setup lang="ts">
import type { DiyPageType } from '~/mall/api/diyPage'
import type { DiyTemplateVo } from '~/mall/api/diyTemplate'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  applyDiyTemplate,
  createDiyTemplate,
  disableDiyTemplate,
  enableDiyTemplate,
  pageDiyTemplates,
  updateDiyTemplate,
} from '~/mall/api/diyTemplate'
import { createDefaultSchema } from '../schema/componentRegistry'

defineOptions({ name: 'mall:diy:template' })

const loading = ref(false)
const rows = ref<DiyTemplateVo[]>([])
const total = ref(0)
const query = reactive({
  name: '',
  category_id: '' as number | '',
  page_key: '',
  page_type: '' as DiyPageType | '',
  is_enabled: '' as boolean | '',
  page: 1,
  page_size: 15,
})
const dialogVisible = ref(false)
const applyVisible = ref(false)
const editing = ref<DiyTemplateVo | null>(null)
const applying = ref<DiyTemplateVo | null>(null)
const form = reactive({
  category_id: 1,
  name: '',
  page_key: 'home',
  page_type: 'all' as DiyPageType,
  cover: '',
  description: '',
  sort: 0,
  is_enabled: true,
})
const applyForm = reactive({
  page_id: undefined as number | undefined,
})

const pageTypeMap: Record<string, string> = {
  miniprogram: '小程序',
  h5: 'H5',
  all: '通用',
}

async function load() {
  loading.value = true
  try {
    const res = await pageDiyTemplates(query)
    rows.value = res.data?.list || []
    total.value = res.data?.total || 0
  }
  finally {
    loading.value = false
  }
}

function resetQuery() {
  query.name = ''
  query.category_id = ''
  query.page_key = ''
  query.page_type = ''
  query.is_enabled = ''
  query.page = 1
  load()
}

function resetForm() {
  form.category_id = 1
  form.name = ''
  form.page_key = 'home'
  form.page_type = 'all'
  form.cover = ''
  form.description = ''
  form.sort = 0
  form.is_enabled = true
}

function openCreate() {
  editing.value = null
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: DiyTemplateVo) {
  editing.value = row
  form.category_id = row.category_id
  form.name = row.name
  form.page_key = row.page_key
  form.page_type = row.page_type
  form.cover = row.cover || ''
  form.description = row.description || ''
  form.sort = row.sort || 0
  form.is_enabled = row.is_enabled !== false
  dialogVisible.value = true
}

function templatePayload() {
  return {
    ...form,
    cover: form.cover || null,
    description: form.description || null,
    schema: editing.value?.schema || createDefaultSchema(form.page_key, form.name || '装修模板'),
  }
}

async function submitForm() {
  if (!form.name || !form.page_key) {
    ElMessage.warning('请填写模板名称和页面键')
    return
  }

  if (editing.value?.id) {
    await updateDiyTemplate(editing.value.id, templatePayload())
    ElMessage.success('模板已更新')
  }
  else {
    await createDiyTemplate(templatePayload())
    ElMessage.success('模板已创建')
  }
  dialogVisible.value = false
  load()
}

async function toggleTemplate(row: DiyTemplateVo) {
  if (row.is_enabled) {
    await disableDiyTemplate(row.id!)
    ElMessage.success('模板已禁用')
  }
  else {
    await enableDiyTemplate(row.id!)
    ElMessage.success('模板已启用')
  }
  load()
}

function openApply(row: DiyTemplateVo) {
  applying.value = row
  applyForm.page_id = undefined
  applyVisible.value = true
}

async function submitApply() {
  const templateId = applying.value?.id
  const pageId = applyForm.page_id
  if (!templateId || !pageId) {
    ElMessage.warning('请输入要套用的页面 ID')
    return
  }

  await ElMessageBox.confirm('套用模板会覆盖目标页面当前草稿，确认继续？', '套用模板', {
    type: 'warning',
  })
  await applyDiyTemplate(templateId, pageId)
  ElMessage.success('模板已套用为页面草稿')
  applyVisible.value = false
}

onMounted(load)
</script>

<template>
  <div class="mine-layout diy-template-list">
    <div class="diy-template-list__bar">
      <div class="diy-template-list__filters">
        <el-input v-model="query.name" clearable placeholder="模板名称" />
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
      <el-button v-auth="['mall:diy:template:create']" type="primary" @click="openCreate">
        <ma-svg-icon name="ph:plus" size="14" />新建模板
      </el-button>
    </div>

    <el-table v-loading="loading" :data="rows" row-key="id" border>
      <el-table-column prop="name" label="模板名称" min-width="170" />
      <el-table-column label="分类" width="130">
        <template #default="{ row }">{{ row.category?.name || row.category_id }}</template>
      </el-table-column>
      <el-table-column prop="page_key" label="页面键" width="140" />
      <el-table-column label="页面类型" width="110">
        <template #default="{ row }">{{ pageTypeMap[row.page_type] || row.page_type }}</template>
      </el-table-column>
      <el-table-column label="启用" width="90">
        <template #default="{ row }">
          <el-tag :type="row.is_enabled ? 'success' : 'info'">{{ row.is_enabled ? '启用' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="sort" label="排序" width="90" />
      <el-table-column prop="updated_at" label="更新时间" width="180" />
      <el-table-column label="操作" width="260" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="openApply(row)">套用</el-button>
          <el-button link @click="openEdit(row)">编辑</el-button>
          <el-button link :type="row.is_enabled ? 'warning' : 'success'" @click="toggleTemplate(row)">
            {{ row.is_enabled ? '禁用' : '启用' }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="diy-template-list__pagination">
      <el-pagination
        v-model:current-page="query.page"
        v-model:page-size="query.page_size"
        layout="total, sizes, prev, pager, next"
        :total="total"
        @change="load"
      />
    </div>

    <el-dialog v-model="dialogVisible" :title="editing ? '编辑模板' : '新建模板'" width="560px">
      <el-form label-width="96px">
        <el-form-item label="分类ID" required>
          <el-input-number v-model="form.category_id" :min="1" controls-position="right" />
        </el-form-item>
        <el-form-item label="模板名称" required>
          <el-input v-model="form.name" />
        </el-form-item>
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
        <el-form-item label="封面">
          <el-input v-model="form.cover" placeholder="图片 URL" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" controls-position="right" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="form.is_enabled" />
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

    <el-dialog v-model="applyVisible" title="套用模板" width="420px">
      <el-form label-width="90px">
        <el-form-item label="模板">
          <span>{{ applying?.name }}</span>
        </el-form-item>
        <el-form-item label="页面ID" required>
          <el-input-number v-model="applyForm.page_id" :min="1" controls-position="right" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="applyVisible = false">取消</el-button>
        <el-button type="primary" @click="submitApply">确认套用</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.diy-template-list {
  padding: 16px;
}

.diy-template-list__bar {
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.diy-template-list__filters {
  display: grid;
  grid-template-columns: 160px 140px 130px 130px auto auto;
  gap: 8px;
}

.diy-template-list__pagination {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}
</style>
