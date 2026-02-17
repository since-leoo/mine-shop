<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="member-level-page p-3">
    <el-card shadow="never" class="mb-4">
      <el-form :model="filters" label-width="100px" inline>
        <el-form-item :label="t('member.levelConfig.keyword')">
          <el-input v-model="filters.keyword" :placeholder="t('member.levelConfig.levelName')" clearable @keyup.enter="handleSearch">
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.status')">
          <el-select v-model="filters.status" :placeholder="t('member.levelConfig.allStatus')" clearable class="w-40" @change="handleSearch">
            <el-option :label="t('member.levelConfig.active')" value="active" />
            <el-option :label="t('member.levelConfig.inactive')" value="inactive" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <template #icon><el-icon><Search /></el-icon></template>
            {{ t('member.levelConfig.search') }}
          </el-button>
          <el-button @click="resetFilters">
            <template #icon><el-icon><Refresh /></el-icon></template>
            {{ t('member.levelConfig.reset') }}
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <span class="font-medium">{{ t('member.levelConfig.title') }}</span>
          <el-button type="primary" @click="openCreate">
            <template #icon><el-icon><Plus /></el-icon></template>
            {{ t('member.levelConfig.createLevel') }}
          </el-button>
        </div>
      </template>

      <el-table :data="levelList" v-loading="loading" border stripe>
        <el-table-column type="index" width="60" label="#" />
        <el-table-column :label="t('member.levelConfig.name')" prop="name" min-width="140" />
        <el-table-column :label="t('member.levelConfig.levelValue')" width="90" prop="level" />
        <el-table-column :label="t('member.levelConfig.growthRange')" min-width="200">
          <template #default="{ row }">
            {{ row.growth_value_min }} - {{ row.growth_value_max ?? '∞' }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.levelConfig.discountRate')" width="100">
          <template #default="{ row }">
            {{ row.discount_rate ?? 100 }}%
          </template>
        </el-table-column>
        <el-table-column :label="t('member.levelConfig.pointRate')" width="120">
          <template #default="{ row }">
            {{ row.point_rate ?? 100 }}%
          </template>
        </el-table-column>
        <el-table-column :label="t('member.levelConfig.statusColumn')" width="110">
          <template #default="{ row }">
            <el-tag :type="row.status === 'active' ? 'success' : 'info'">
              {{ row.status === 'active' ? t('member.levelConfig.active') : t('member.levelConfig.inactive') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('member.levelConfig.sortOrder')" width="90" prop="sort_order" />
        <el-table-column :label="t('member.levelConfig.operation')" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openEdit(row)">
              <el-icon><EditPen /></el-icon>
              {{ t('member.levelConfig.edit') }}
            </el-button>
            <el-popconfirm :title="t('member.levelConfig.deleteConfirm')" @confirm="handleDelete(row.id)">
              <template #reference>
                <el-button type="danger" link size="small">
                  <el-icon><Delete /></el-icon>
                  {{ t('member.levelConfig.delete') }}
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
        <el-form-item :label="t('member.levelConfig.nameLabel')" prop="name">
          <el-input v-model="form.name" :placeholder="t('member.levelConfig.namePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.levelLabel')" prop="level">
          <el-input-number v-model="form.level" :min="1" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.growthMinLabel')" prop="growth_value_min">
          <el-input-number v-model="form.growth_value_min" :min="0" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.growthMaxLabel')">
          <el-input-number v-model="form.growth_value_max" :min="form.growth_value_min ?? 0" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.discountRateLabel')">
          <el-input-number v-model="form.discount_rate" :min="0" :max="100" :step="1" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.pointRateLabel')">
          <el-input-number v-model="form.point_rate" :min="0" :max="1000" :step="10" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.colorLabel')">
          <el-color-picker v-model="form.color" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.iconLabel')">
          <el-input v-model="form.icon" :placeholder="t('member.levelConfig.iconPlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.statusLabel')" prop="status">
          <el-select v-model="form.status">
            <el-option :label="t('member.levelConfig.active')" value="active" />
            <el-option :label="t('member.levelConfig.inactive')" value="inactive" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.sortOrderLabel')">
          <el-input-number v-model="form.sort_order" :min="0" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.levelConfig.descriptionLabel')">
          <el-input v-model="form.description" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="drawerVisible = false">{{ t('member.levelConfig.cancel') }}</el-button>
        <el-button type="primary" :loading="submitLoading" @click="submitForm">
          <template #icon><el-icon><Check /></el-icon></template>
          {{ t('member.levelConfig.save') }}
        </el-button>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Check, Delete, EditPen, Plus, Refresh, Search } from '@element-plus/icons-vue'
import { memberLevelApi, type MemberLevel } from '~/member/api/member'

defineOptions({ name: 'member:level' })

const { t } = useI18n()

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
const drawerTitle = ref('')
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

const rules = computed<FormRules>(() => ({
  name: [{ required: true, message: t('member.levelConfig.nameRequired'), trigger: 'blur' }],
  level: [{ required: true, message: t('member.levelConfig.levelRequired'), trigger: 'blur' }],
  growth_value_min: [{ required: true, message: t('member.levelConfig.growthMinRequired'), trigger: 'blur' }],
  status: [{ required: true, message: t('member.levelConfig.statusLabel'), trigger: 'change' }],
}))

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
    ElMessage.error(error?.message || t('member.levelConfig.loadFailed'))
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
  drawerTitle.value = t('member.levelConfig.drawerCreateTitle')
  isEdit.value = false
  editingId.value = null
  drawerVisible.value = true
}

const openEdit = (row: MemberLevel) => {
  resetForm()
  Object.assign(form, row)
  drawerTitle.value = t('member.levelConfig.drawerEditTitle')
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
      ElMessage.success(t('member.levelConfig.levelUpdated'))
    }
    else {
      await memberLevelApi.create(form)
      ElMessage.success(t('member.levelConfig.levelCreated'))
    }
    drawerVisible.value = false
    loadLevels()
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('member.levelConfig.saveFailed'))
  }
  finally {
    submitLoading.value = false
  }
}

const handleDelete = async (id: number) => {
  try {
    await memberLevelApi.delete(id)
    ElMessage.success(t('member.levelConfig.deleted'))
    loadLevels()
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('member.levelConfig.deleteFailed'))
  }
}

loadLevels()
</script>
