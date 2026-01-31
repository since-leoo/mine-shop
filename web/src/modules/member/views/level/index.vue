<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="member-level-page">
    <el-card shadow="never" class="mb-4">
      <el-form :model="filters" label-width="90px" inline>
        <el-form-item label="关键字">
          <el-input v-model="filters.keyword" placeholder="等级名称" clearable @keyup.enter="handleSearch">
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="filters.status" placeholder="全部状态" clearable class="w-40" @change="handleSearch">
            <el-option label="启用" value="active" />
            <el-option label="停用" value="inactive" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <template #icon><el-icon><Search /></el-icon></template>
            搜索
          </el-button>
          <el-button @click="resetFilters">
            <template #icon><el-icon><Refresh /></el-icon></template>
            重置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <span class="font-medium">等级配置</span>
          <el-button type="primary" @click="openCreate">
            <template #icon><el-icon><Plus /></el-icon></template>
            新建等级
          </el-button>
        </div>
      </template>

      <el-table :data="levelList" v-loading="loading" border stripe>
        <el-table-column type="index" width="60" label="#" />
        <el-table-column label="名称" prop="name" min-width="140" />
        <el-table-column label="等级值" width="90" prop="level" />
        <el-table-column label="成长区间" min-width="200">
          <template #default="{ row }">
            {{ row.growth_value_min }} - {{ row.growth_value_max ?? '∞' }}
          </template>
        </el-table-column>
        <el-table-column label="折扣率" width="100">
          <template #default="{ row }">
            {{ row.discount_rate ?? 100 }}%
          </template>
        </el-table-column>
        <el-table-column label="积分倍率" width="120">
          <template #default="{ row }">
            {{ row.point_rate ?? 100 }}%
          </template>
        </el-table-column>
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <el-tag :type="row.status === 'active' ? 'success' : 'info'">
              {{ row.status === 'active' ? '启用' : '停用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="排序" width="90" prop="sort_order" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openEdit(row)">
              <el-icon><EditPen /></el-icon>
              编辑
            </el-button>
            <el-popconfirm title="确认删除该等级？" @confirm="handleDelete(row.id)">
              <template #reference>
                <el-button type="danger" link size="small">
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
          layout="total, prev, pager, next, jumper"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <el-drawer v-model="drawerVisible" :title="drawerTitle" size="480px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入等级名称" />
        </el-form-item>
        <el-form-item label="等级值" prop="level">
          <el-input-number v-model="form.level" :min="1" class="w-full" />
        </el-form-item>
        <el-form-item label="成长值下限" prop="growth_value_min">
          <el-input-number v-model="form.growth_value_min" :min="0" class="w-full" />
        </el-form-item>
        <el-form-item label="成长值上限">
          <el-input-number v-model="form.growth_value_max" :min="form.growth_value_min ?? 0" class="w-full" />
        </el-form-item>
        <el-form-item label="折扣率(%)">
          <el-input-number v-model="form.discount_rate" :min="0" :max="100" :step="1" class="w-full" />
        </el-form-item>
        <el-form-item label="积分倍率(%)">
          <el-input-number v-model="form.point_rate" :min="0" :max="1000" :step="10" class="w-full" />
        </el-form-item>
        <el-form-item label="颜色">
          <el-color-picker v-model="form.color" class="w-full" />
        </el-form-item>
        <el-form-item label="图标">
          <el-input v-model="form.icon" placeholder="Iconify 名称" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="form.status">
            <el-option label="启用" value="active" />
            <el-option label="停用" value="inactive" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" class="w-full" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="drawerVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="submitForm">
          <template #icon><el-icon><Check /></el-icon></template>
          保存
        </el-button>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Check, Delete, EditPen, Plus, Refresh, Search } from '@element-plus/icons-vue'
import { memberLevelApi, type MemberLevel } from '~/member/api/member'

defineOptions({ name: 'member:level' })

const loading = ref(false)
const levelList = ref<MemberLevel[]>([])
const filters = reactive({
  keyword: '',
  status: '',
})

const pagination = reactive({
  page: 1,
  pageSize: 15,
  total: 0,
})

const drawerVisible = ref(false)
const drawerTitle = ref('新建等级')
const isEdit = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const submitLoading = ref(false)

const form = reactive<Partial<MemberLevel>>({
  name: '',
  level: 1,
  growth_value_min: 0,
  growth_value_max: undefined,
  discount_rate: 100,
  point_rate: 100,
  color: '',
  icon: '',
  status: 'active',
  sort_order: 0,
  description: '',
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入等级名称', trigger: 'blur' }],
  level: [{ required: true, message: '请输入等级值', trigger: 'blur' }],
  growth_value_min: [{ required: true, message: '请输入成长值下限', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const buildParams = () => ({
  keyword: filters.keyword || undefined,
  status: filters.status || undefined,
  page: pagination.page,
  page_size: pagination.pageSize,
})

const loadLevels = async () => {
  loading.value = true
  try {
    const res = await memberLevelApi.list(buildParams())
    levelList.value = res.data.list
    pagination.total = res.data.total
  }
  catch (error: any) {
    ElMessage.error(error?.message || '加载失败')
  }
  finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  loadLevels()
}

const resetFilters = () => {
  filters.keyword = ''
  filters.status = ''
  handleSearch()
}

const handlePageChange = (page: number) => {
  pagination.page = page
  loadLevels()
}

const resetForm = () => {
  Object.assign(form, {
    name: '',
    level: 1,
    growth_value_min: 0,
    growth_value_max: undefined,
    discount_rate: 100,
    point_rate: 100,
    color: '',
    icon: '',
    status: 'active',
    sort_order: 0,
    description: '',
  })
}

const openCreate = () => {
  resetForm()
  drawerTitle.value = '新建等级'
  isEdit.value = false
  editingId.value = null
  drawerVisible.value = true
}

const openEdit = (row: MemberLevel) => {
  resetForm()
  Object.assign(form, row)
  drawerTitle.value = '编辑等级'
  isEdit.value = true
  editingId.value = row.id
  drawerVisible.value = true
}

const submitForm = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  submitLoading.value = true
  try {
    if (isEdit.value && editingId.value) {
      await memberLevelApi.update(editingId.value, form)
      ElMessage.success('等级已更新')
    }
    else {
      await memberLevelApi.create(form)
      ElMessage.success('等级已创建')
    }
    drawerVisible.value = false
    loadLevels()
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
    await memberLevelApi.delete(id)
    ElMessage.success('已删除')
    loadLevels()
  }
  catch (error: any) {
    ElMessage.error(error?.message || '删除失败')
  }
}

loadLevels()
</script>
