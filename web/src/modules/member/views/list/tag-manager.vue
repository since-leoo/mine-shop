<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <el-dialog :model-value="visible" title="标签管理" width="720px" @close="handleClose">
    <div class="flex items-center justify-between mb-3">
      <el-input v-model="keyword" placeholder="搜索标签名称" class="w-60" clearable @keyup.enter="loadData">
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
      <div class="flex items-center gap-2">
        <el-select v-model="status" placeholder="全部状态" clearable class="w-40" @change="loadData">
          <el-option label="启用" value="active" />
          <el-option label="停用" value="inactive" />
        </el-select>
        <el-button type="primary" @click="openCreate">
          <template #icon><el-icon><Plus /></el-icon></template>
          新建标签
        </el-button>
      </div>
    </div>

    <el-table :data="tagList" v-loading="loading" border stripe>
      <el-table-column type="index" label="#" width="60" />
      <el-table-column label="名称" prop="name" min-width="140" />
      <el-table-column label="颜色" width="120">
        <template #default="{ row }">
          <el-tag v-if="row.color" :style="{ borderColor: row.color, color: row.color }">
            {{ row.color }}
          </el-tag>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 'active' ? 'success' : 'info'">
            {{ row.status === 'active' ? '启用' : '停用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="排序" prop="sort_order" width="80" />
      <el-table-column label="操作" width="180">
        <template #default="{ row }">
          <el-button link size="small" type="primary" @click="openEdit(row)">
            <el-icon><EditPen /></el-icon>
            编辑
          </el-button>
          <el-popconfirm title="确认删除该标签？" @confirm="handleDelete(row.id)">
            <template #reference>
              <el-button link size="small" type="danger">
                <el-icon><Delete /></el-icon>
                删除
              </el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>

    <div class="flex justify-end mt-4">
      <el-pagination
        :current-page="pagination.page"
        :page-size="pagination.pageSize"
        :total="pagination.total"
        layout="total, prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>

    <el-drawer v-model="formVisible" :title="isEdit ? '编辑标签' : '新建标签'" size="400px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入标签名称" />
        </el-form-item>
        <el-form-item label="颜色">
          <el-color-picker v-model="form.color" show-alpha class="w-full" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="启用" value="active" />
            <el-option label="停用" value="inactive" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" :max="9999" class="w-full" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" rows="3" placeholder="可选，标签说明" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="submitForm">
          <template #icon><el-icon><Check /></el-icon></template>
          保存
        </el-button>
      </template>
    </el-drawer>
  </el-dialog>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Check, Delete, EditPen, Plus, Search } from '@element-plus/icons-vue'
import { memberTagApi, type MemberTag, type MemberTagPayload } from '~/member/api/member'

defineOptions({ name: 'MemberTagManager' })

const props = defineProps<{ visible: boolean }>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  updated: []
}>()

const loading = ref(false)
const tagList = ref<MemberTag[]>([])
const keyword = ref('')
const status = ref<string>()

const pagination = reactive({
  page: 1,
  pageSize: 10,
  total: 0,
})

const formVisible = ref(false)
const formRef = ref<FormInstance>()
const submitLoading = ref(false)
const isEdit = ref(false)
const editingId = ref<number | null>(null)

const form = reactive<MemberTagPayload>({
  name: '',
  color: '',
  status: 'active',
  sort_order: 0,
  description: '',
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入标签名称', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const buildParams = () => ({
  keyword: keyword.value || undefined,
  status: status.value || undefined,
  page: pagination.page,
  page_size: pagination.pageSize,
})

const loadData = async () => {
  if (!props.visible) return
  loading.value = true
  try {
    const res = await memberTagApi.list(buildParams())
    tagList.value = res.data.list
    pagination.total = res.data.total
  }
  catch (error: any) {
    ElMessage.error(error?.message || '加载标签失败')
  }
  finally {
    loading.value = false
  }
}

const handlePageChange = (page: number) => {
  pagination.page = page
  loadData()
}

const resetForm = () => {
  editingId.value = null
  Object.assign(form, {
    name: '',
    color: '',
    description: '',
    status: 'active',
    sort_order: 0,
  })
}

const openCreate = () => {
  resetForm()
  isEdit.value = false
  formVisible.value = true
}

const openEdit = (row: MemberTag) => {
  editingId.value = row.id
  isEdit.value = true
  Object.assign(form, {
    name: row.name,
    color: row.color,
    description: row.description,
    status: row.status,
    sort_order: row.sort_order,
  })
  formVisible.value = true
}

const submitForm = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  submitLoading.value = true
  try {
    if (isEdit.value && editingId.value) {
      await memberTagApi.update(editingId.value, form)
      ElMessage.success('标签已更新')
    }
    else {
      await memberTagApi.create(form)
      ElMessage.success('标签已创建')
    }
    formVisible.value = false
    emit('updated')
    loadData()
  }
  catch (error: any) {
    ElMessage.error(error?.message || '保存失败')
  }
  finally {
    submitLoading.value = false
  }
}

const handleDelete = async (id: number) => {
  try {
    await memberTagApi.delete(id)
    ElMessage.success('标签已删除')
    emit('updated')
    loadData()
  }
  catch (error: any) {
    ElMessage.error(error?.message || '删除失败')
  }
}

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      loadData()
    }
  },
)

onMounted(() => {
  if (props.visible) {
    loadData()
  }
})

const handleClose = () => emit('update:visible', false)
</script>
