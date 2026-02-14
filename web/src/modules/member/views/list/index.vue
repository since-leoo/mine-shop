<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="member-page p-3">
    <el-card shadow="never" class="mb-4">
      <el-form label-width="90px" :model="filters">
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item :label="t('member.list.keyword')">
              <el-input
                v-model="filters.keyword"
                :placeholder="t('member.list.keywordPlaceholder')"
                clearable
                @keyup.enter="handleSearch"
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('member.list.statusLabel')">
              <el-select v-model="filters.status" :placeholder="t('mall.allStatus')" clearable class="w-full" @change="handleSearch">
                <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('member.list.levelLabel')">
              <el-select v-model="filters.level" :placeholder="t('mall.allLevel')" clearable class="w-full" @change="handleSearch">
                <el-option v-for="item in levelOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('member.list.sourceLabel')">
              <el-select v-model="filters.source" :placeholder="t('mall.allSource')" clearable class="w-full" @change="handleSearch">
                <el-option v-for="item in sourceOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item :label="t('member.list.tagLabel')">
              <el-select v-model="filters.tag_id" :placeholder="t('mall.allTag')" clearable filterable class="w-full" @change="handleSearch">
                <el-option v-for="tag in tagOptions" :key="tag.id" :label="tag.name" :value="tag.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="10">
            <el-form-item :label="t('member.list.registeredAt')">
              <el-date-picker
                v-model="createdRange"
                value-format="YYYY-MM-DD"
                type="daterange"
                :start-placeholder="t('mall.startDate')"
                :end-placeholder="t('mall.endDate')"
                class="w-full"
                @change="handleDateChange"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8" class="text-right">
            <el-button type="primary" @click="handleSearch">
              <template #icon><el-icon><Search /></el-icon></template>
              {{ t('member.list.search') }}
            </el-button>
            <el-button @click="resetFilters">
              <template #icon><el-icon><Refresh /></el-icon></template>
              {{ t('member.list.reset') }}
            </el-button>
            <el-button @click="tagManagerVisible = true">
              <template #icon><el-icon><Collection /></el-icon></template>
              {{ t('member.list.tagManager') }}
            </el-button>
          </el-col>
        </el-row>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <span class="font-medium">{{ t('member.list.memberList') }}</span>
          <div class="flex items-center gap-2">
            <el-button type="primary" size="small" v-auth="['member:member:create']" @click="openCreate">
              <template #icon><el-icon><Plus /></el-icon></template>
              {{ t('member.list.createMember') }}
            </el-button>
            <el-button size="small" @click="loadMembers">
              <template #icon><el-icon><Refresh /></el-icon></template>
              {{ t('member.list.refresh') }}
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="memberList" v-loading="loading" border stripe row-key="id">
        <el-table-column type="index" label="#" width="60" />
        <el-table-column :label="t('member.list.memberColumn')" min-width="200">
          <template #default="{ row }">
            <div class="flex items-center gap-3">
              <el-avatar :size="40" :src="row.avatar">
                {{ row.nickname?.slice(0, 1) || 'U' }}
              </el-avatar>
              <div class="text-left">
                <div class="font-medium">{{ row.nickname || t('member.list.noNickname') }}</div>
                <div class="text-xs text-gray-500">ID: {{ row.id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.phoneColumn')" width="140">
          <template #default="{ row }">
            {{ row.phone || '-' }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.tagColumn')" min-width="200">
          <template #default="{ row }">
            <div class="flex flex-wrap gap-1">
              <el-tag
                v-for="tag in row.tags"
                :key="tag.id"
                size="small"
                :type="tag.status === 'active' ? 'success' : 'info'"
                :style="{ borderColor: tag.color, color: tag.color }"
              >
                {{ tag.name }}
              </el-tag>
              <span v-if="!row.tags?.length" class="text-gray-400 text-sm">-</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.levelColumn')" width="100">
          <template #default="{ row }">
            <el-tag type="warning" size="small">
              {{ levelLabelMap[row.level || 'bronze'] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.growthValue')" width="120">
          <template #default="{ row }">
            {{ row.growth_value ?? 0 }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.balanceColumn')" width="120">
          <template #default="{ row }">
            ¥{{ formatYuan(row.wallet?.balance) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.pointsColumn')" width="120">
          <template #default="{ row }">
            {{ row.points_wallet?.balance ?? row.points_balance ?? 0 }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.orderCount')" width="100" prop="total_orders" />
        <el-table-column :label="t('member.list.totalSpent')" width="120">
          <template #default="{ row }">
            ¥{{ formatYuan(row.total_amount) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.lastLogin')" width="170">
          <template #default="{ row }">
            {{ row.last_login_at ? formatDateTime(row.last_login_at) : t('member.list.noLogin') }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.statusColumn')" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTagTypeMap[row.status]" size="small">
              {{ statusLabelMap[row.status] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.sourceColumn')" width="120">
          <template #default="{ row }">
            {{ sourceLabelMap[row.source || 'wechat'] }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.registeredAtColumn')" width="170">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.list.operation')" fixed="right" width="260">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-2">
              <el-button type="primary" link size="small" @click="openDetail(row)">
                <el-icon><View /></el-icon>
                {{ t('member.list.detail') }}
              </el-button>
              <el-button type="primary" link size="small" @click="openEdit(row)">
                <el-icon><EditPen /></el-icon>
                {{ t('member.list.edit') }}
              </el-button>
              <el-button type="success" link size="small" @click="openTagDrawer(row)">
                <el-icon><Collection /></el-icon>
                {{ t('member.list.assignTag') }}
              </el-button>
              <el-popconfirm
                :title="row.status === 'banned' ? t('member.list.unbanConfirm') : t('member.list.banConfirm')"
                @confirm="toggleStatus(row)"
              >
                <template #reference>
                  <el-button :type="row.status === 'banned' ? 'warning' : 'danger'" link size="small">
                    <el-icon><Lock /></el-icon>
                    {{ row.status === 'banned' ? t('member.list.unban') : t('member.list.ban') }}
                  </el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <div class="flex justify-end mt-4">
        <el-pagination
          :current-page="pagination.page"
          :page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <MemberDetail v-model:visible="detailVisible" :member-id="currentMemberId" />
    <TagManager v-model:visible="tagManagerVisible" @updated="loadTagOptions" />

    <el-drawer v-model="editVisible" :title="editDrawerTitle" size="480px">
      <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="100px">
        <el-form-item :label="t('member.list.nicknameLabel')" prop="nickname">
          <el-input v-model="editForm.nickname" :placeholder="t('member.list.nicknamePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('member.list.phoneColumn')">
          <el-input v-model="editForm.phone" :placeholder="t('member.list.phonePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('member.list.genderLabel')">
          <el-select v-model="editForm.gender" :placeholder="t('member.list.genderPlaceholder')" clearable>
            <el-option :label="t('member.list.genderUnknown')" value="unknown" />
            <el-option :label="t('member.list.genderMale')" value="male" />
            <el-option :label="t('member.list.genderFemale')" value="female" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('member.list.levelColumn')">
          <el-select v-model="editForm.level" :placeholder="t('mall.allLevel')">
            <el-option v-for="item in levelOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('member.list.growthValueLabel')">
          <el-input-number v-model="editForm.growth_value" :min="0" class="w-full" />
        </el-form-item>
        <el-form-item :label="t('member.list.statusLabel')">
          <el-select v-model="editForm.status">
            <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('member.list.sourceLabel')">
          <el-select v-model="editForm.source">
            <el-option v-for="item in sourceOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('member.list.regionLabel')">
          <el-cascader
            v-model="editForm.regionCodes"
            :options="geoTree"
            :props="geoCascaderProps"
            clearable
            filterable
            collapse-tags
            :show-all-levels="false"
            class="w-full"
            @change="handleRegionChange"
          />
        </el-form-item>
        <el-form-item :label="t('member.list.remarkLabel')">
          <el-input v-model="editForm.remark" type="textarea" rows="3" :placeholder="t('member.list.remarkPlaceholder')" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">{{ t('member.list.cancel') }}</el-button>
        <el-button type="primary" :loading="editLoading" @click="submitEdit">
          <template #icon><el-icon><Check /></el-icon></template>
          {{ submitButtonText }}
        </el-button>
      </template>
    </el-drawer>

    <el-drawer v-model="tagDrawerVisible" :title="t('member.list.tagDrawerTitle')" size="420px">
      <el-form label-width="90px">
        <el-form-item :label="t('member.list.tagMember')">
          <div class="text-base font-medium">{{ currentMember?.nickname || '-' }} (ID: {{ currentMember?.id }})</div>
        </el-form-item>
        <el-form-item :label="t('member.list.selectTag')">
          <el-select v-model="selectedTags" multiple filterable class="w-full" :placeholder="t('member.list.selectTagPlaceholder')">
            <el-option v-for="tag in tagOptions" :key="tag.id" :label="tag.name" :value="tag.id">
              <span class="flex items-center gap-2">
                <el-tag :style="{ borderColor: tag.color, color: tag.color }" size="small">{{ tag.name }}</el-tag>
                <small class="text-gray-400">{{ tag.status === 'active' ? t('member.list.tagEnabled') : t('member.list.tagDisabled') }}</small>
              </span>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="tagDrawerVisible = false">{{ t('member.list.cancel') }}</el-button>
        <el-button type="primary" :loading="tagLoading" @click="saveTags">
          <template #icon><el-icon><Check /></el-icon></template>
          {{ t('member.list.save') }}
        </el-button>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import dayjs from 'dayjs'
import { Check, Collection, EditPen, Lock, Plus, Refresh, Search, View } from '@element-plus/icons-vue'
import TagManager from './tag-manager.vue'
import MemberDetail from './detail.vue'
import { useGeo } from '@/hooks/useGeo.ts'
import { memberApi, memberTagApi, type MallMember, type MemberTag } from '~/member/api/member'
import { formatYuan } from '@/utils/price'

defineOptions({ name: 'member:list' })

const { t } = useI18n()

const { geoTree, ensureGeoTree, getRegionNames, getRegionPath } = useGeo()
const geoCascaderProps = {
  value: 'code',
  label: 'name',
  children: 'children',
  emitPath: true,
  checkStrictly: false,
  expandTrigger: 'hover' as const,
  showAllLevels: false,
}

const loading = ref(false)
const memberList = ref<MallMember[]>([])
const createdRange = ref<[string, string] | null>(null)
const tagOptions = ref<MemberTag[]>([])

const filters = reactive({
  keyword: '',
  status: '',
  level: '',
  source: '',
  tag_id: undefined as number | undefined,
  created_start: '',
  created_end: '',
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
})

const statusOptions = computed(() => [
  { label: t('member.status.active'), value: 'active' },
  { label: t('member.status.inactive'), value: 'inactive' },
  { label: t('member.status.banned'), value: 'banned' },
])

const levelOptions = computed(() => [
  { label: t('member.level.bronze'), value: 'bronze' },
  { label: t('member.level.silver'), value: 'silver' },
  { label: t('member.level.gold'), value: 'gold' },
  { label: t('member.level.diamond'), value: 'diamond' },
])

const sourceOptions = computed(() => [
  { label: t('member.source.wechat'), value: 'wechat' },
  { label: t('member.source.miniProgram'), value: 'mini_program' },
  { label: t('member.source.h5'), value: 'h5' },
  { label: t('member.source.admin'), value: 'admin' },
])

const statusLabelMap = computed<Record<string, string>>(() => ({
  active: t('member.status.active'),
  inactive: t('member.status.inactive'),
  banned: t('member.status.banned'),
}))

const statusTagTypeMap: Record<string, 'success' | 'info' | 'danger'> = {
  active: 'success',
  inactive: 'info',
  banned: 'danger',
}

const levelLabelMap = computed<Record<string, string>>(() => ({
  bronze: t('member.level.bronze'),
  silver: t('member.level.silver'),
  gold: t('member.level.gold'),
  diamond: t('member.level.diamond'),
}))

const sourceLabelMap = computed<Record<string, string>>(() => ({
  wechat: t('member.source.wechat'),
  mini_program: t('member.source.miniProgram'),
  h5: t('member.source.h5'),
  admin: t('member.source.admin'),
}))

const formatDateTime = (value?: string | null) => (value ? dayjs(value).format('YYYY-MM-DD HH:mm') : '-')

const buildParams = () => {
  const params: Record<string, any> = {
    ...filters,
    page: pagination.page,
    page_size: pagination.pageSize,
  }
  Object.keys(params).forEach((key) => {
    if (params[key] === '' || params[key] === null || params[key] === undefined) {
      delete params[key]
    }
  })
  return params
}

const loadMembers = async () => {
  loading.value = true
  try {
    const res = await memberApi.list(buildParams())
    memberList.value = res.data.list
    pagination.total = res.data.total
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('member.list.loadFailed'))
  }
  finally {
    loading.value = false
  }
}

const loadTagOptions = async () => {
  try {
    const res = await memberTagApi.options()
    tagOptions.value = res.data
  }
  catch (error) {
    console.error(t('member.list.loadTagFailed'), error)
  }
}

const handleSearch = () => {
  pagination.page = 1
  loadMembers()
}

const resetFilters = () => {
  Object.assign(filters, {
    keyword: '',
    status: '',
    level: '',
    source: '',
    tag_id: undefined,
    created_start: '',
    created_end: '',
  })
  createdRange.value = null
  handleSearch()
}

const handleDateChange = (range: [string, string] | null) => {
  if (range) {
    filters.created_start = range[0]
    filters.created_end = range[1]
  }
  else {
    filters.created_start = ''
    filters.created_end = ''
  }
  handleSearch()
}

const handleSizeChange = (size: number) => {
  pagination.pageSize = size
  pagination.page = 1
  loadMembers()
}

const handlePageChange = (page: number) => {
  pagination.page = page
  loadMembers()
}

onMounted(() => {
  loadMembers()
  loadTagOptions()
})

const detailVisible = ref(false)
const currentMemberId = ref<number | null>(null)

const openDetail = (row: MallMember) => {
  currentMemberId.value = row.id
  detailVisible.value = true
}

const editVisible = ref(false)
const editLoading = ref(false)
const editFormRef = ref<FormInstance>()

const getDefaultEditForm = () => ({
  id: 0,
  nickname: '',
  phone: '',
  gender: 'unknown' as 'unknown' | 'male' | 'female',
  level: 'bronze',
  growth_value: 0,
  status: 'active',
  source: 'admin',
  remark: '',
  province: '',
  city: '',
  district: '',
  street: '',
  region_path: '',
  regionCodes: [] as string[],
  country: '中国',
})

const editForm = reactive(getDefaultEditForm())

const resetEditForm = () => {
  Object.assign(editForm, getDefaultEditForm())
}

const isCreateMode = ref(false)
const editDrawerTitle = computed(() => (isCreateMode.value ? t('member.list.createTitle') : t('member.list.editTitle')))
const submitButtonText = computed(() => (isCreateMode.value ? t('member.list.create') : t('member.list.save')))

const editRules = computed<FormRules>(() => ({
  nickname: [{ required: true, message: t('member.list.nicknameRequired'), trigger: 'blur' }],
  status: [{ required: true, message: t('member.list.statusRequired'), trigger: 'change' }],
}))

const openCreate = async () => {
  await ensureGeoTree()
  resetEditForm()
  isCreateMode.value = true
  editVisible.value = true
}

const openEdit = async (row: MallMember) => {
  await ensureGeoTree()
  isCreateMode.value = false
  Object.assign(editForm, {
    id: row.id,
    nickname: row.nickname || '',
    phone: row.phone || '',
    gender: (row.gender as 'unknown' | 'male' | 'female') || 'unknown',
    level: row.level || 'bronze',
    growth_value: row.growth_value ?? 0,
    status: row.status || 'active',
    source: row.source || 'admin',
    remark: row.remark || '',
    province: row.province || '',
    city: row.city || '',
    district: row.district || '',
    street: row.street || '',
    region_path: row.region_path || '',
    country: row.country || '中国',
  })
  const codes = row.region_path ? row.region_path.split('|').filter(Boolean) : []
  editForm.regionCodes = codes
  if (codes.length)
    applyRegionSelection(codes)

  editVisible.value = true
}

const applyRegionSelection = (codes?: (string | number)[]) => {
  const normalized = (codes ?? []).map(code => String(code))
  editForm.regionCodes = normalized
  const names = getRegionNames(normalized)
  editForm.province = names[0] || editForm.province || ''
  editForm.city = names[1] || editForm.city || ''
  editForm.district = names[2] || editForm.district || ''
  editForm.street = names[3] || editForm.street || ''
  editForm.region_path = getRegionPath(normalized)
  if (!editForm.country)
    editForm.country = '中国'
}

const handleRegionChange = (codes: (string | number)[]) => {
  applyRegionSelection(codes)
}

const submitEdit = async () => {
  if (!editFormRef.value) return
  await editFormRef.value.validate()
  editLoading.value = true
  const payload = {
    nickname: editForm.nickname,
    phone: editForm.phone,
    gender: editForm.gender,
    level: editForm.level,
    growth_value: editForm.growth_value,
    status: editForm.status,
    source: editForm.source,
    remark: editForm.remark,
    province: editForm.province || null,
    city: editForm.city || null,
    district: editForm.district || null,
    street: editForm.street || null,
    region_path: editForm.region_path || null,
    country: editForm.country || '中国',
  }
  try {
    if (isCreateMode.value) {
      await memberApi.create(payload)
      ElMessage.success(t('member.list.memberCreated'))
    }
    else {
      await memberApi.update(editForm.id, payload)
      ElMessage.success(t('member.list.memberUpdated'))
    }
    editVisible.value = false
    loadMembers()
  }
  catch (error: any) {
    ElMessage.error(error?.message || (isCreateMode.value ? t('member.list.createFailed') : t('member.list.updateFailed')))
  }
  finally {
    editLoading.value = false
  }
}

const tagDrawerVisible = ref(false)
const tagLoading = ref(false)
const selectedTags = ref<number[]>([])
const currentMember = ref<MallMember | null>(null)

const openTagDrawer = (row: MallMember) => {
  currentMember.value = row
  selectedTags.value = row.tags?.map((tag) => tag.id) || []
  tagDrawerVisible.value = true
}

const saveTags = async () => {
  if (!currentMember.value) return
  tagLoading.value = true
  try {
    await memberApi.syncTags(currentMember.value.id, selectedTags.value)
    ElMessage.success(t('member.list.tagUpdated'))
    tagDrawerVisible.value = false
    currentMember.value = null
    loadMembers()
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('member.list.tagUpdateFailed'))
  }
  finally {
    tagLoading.value = false
  }
}

const toggleStatus = async (row: MallMember) => {
  const nextStatus = row.status === 'banned' ? 'active' : 'banned'
  try {
    await memberApi.updateStatus(row.id, nextStatus)
    ElMessage.success(nextStatus === 'banned' ? t('member.list.memberBanned') : t('member.list.memberUnbanned'))
    loadMembers()
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('member.list.loadFailed'))
  }
}

const tagManagerVisible = ref(false)
</script>
