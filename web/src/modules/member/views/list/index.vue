<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="member-page">
    <el-card shadow="never" class="mb-4">
      <el-form label-width="90px" :model="filters">
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item label="关键词">
              <el-input
                v-model="filters.keyword"
                placeholder="昵称 / 手机号 / OpenID"
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
            <el-form-item label="状态">
              <el-select v-model="filters.status" placeholder="全部状态" clearable class="w-full" @change="handleSearch">
                <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="等级">
              <el-select v-model="filters.level" placeholder="全部等级" clearable class="w-full" @change="handleSearch">
                <el-option v-for="item in levelOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="来源">
              <el-select v-model="filters.source" placeholder="全部来源" clearable class="w-full" @change="handleSearch">
                <el-option v-for="item in sourceOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item label="标签">
              <el-select v-model="filters.tag_id" placeholder="全部标签" clearable filterable class="w-full" @change="handleSearch">
                <el-option v-for="tag in tagOptions" :key="tag.id" :label="tag.name" :value="tag.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="10">
            <el-form-item label="注册时间">
              <el-date-picker
                v-model="createdRange"
                value-format="YYYY-MM-DD"
                type="daterange"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
                class="w-full"
                @change="handleDateChange"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8" class="text-right">
            <el-button type="primary" @click="handleSearch">
              <template #icon><el-icon><Search /></el-icon></template>
              搜索
            </el-button>
            <el-button @click="resetFilters">
              <template #icon><el-icon><Refresh /></el-icon></template>
              重置
            </el-button>
            <el-button @click="tagManagerVisible = true">
              <template #icon><el-icon><Collection /></el-icon></template>
              标签管理
            </el-button>
          </el-col>
        </el-row>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <span class="font-medium">会员列表</span>
          <div class="flex items-center gap-2">
            <el-button type="primary" size="small" v-auth="['member:member:create']" @click="openCreate">
              <template #icon><el-icon><Plus /></el-icon></template>
              新增会员
            </el-button>
            <el-button size="small" @click="loadMembers">
              <template #icon><el-icon><Refresh /></el-icon></template>
              刷新
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="memberList" v-loading="loading" border stripe row-key="id">
        <el-table-column type="index" label="#" width="60" />
        <el-table-column label="会员" min-width="200">
          <template #default="{ row }">
            <div class="flex items-center gap-3">
              <el-avatar :size="40" :src="row.avatar">
                {{ row.nickname?.slice(0, 1) || 'U' }}
              </el-avatar>
              <div class="text-left">
                <div class="font-medium">{{ row.nickname || '未设置昵称' }}</div>
                <div class="text-xs text-gray-500">ID: {{ row.id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="手机号" width="140">
          <template #default="{ row }">
            {{ row.phone || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="标签" min-width="200">
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
        <el-table-column label="等级" width="100">
          <template #default="{ row }">
            <el-tag type="warning" size="small">
              {{ levelLabelMap[row.level || 'bronze'] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="成长值" width="120">
          <template #default="{ row }">
            {{ row.growth_value ?? 0 }}
          </template>
        </el-table-column>
        <el-table-column label="积分" width="120">
          <template #default="{ row }">
            {{ row.points_wallet?.balance ?? row.points_balance ?? 0 }}
          </template>
        </el-table-column>
        <el-table-column label="订单数" width="100" prop="total_orders" />
        <el-table-column label="累计消费" width="120">
          <template #default="{ row }">
            ¥{{ row.total_amount || 0 }}
          </template>
        </el-table-column>
        <el-table-column label="最近登录" width="170">
          <template #default="{ row }">
            {{ row.last_login_at ? formatDateTime(row.last_login_at) : '暂无' }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTagTypeMap[row.status]" size="small">
              {{ statusLabelMap[row.status] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="来源" width="120">
          <template #default="{ row }">
            {{ sourceLabelMap[row.source || 'wechat'] }}
          </template>
        </el-table-column>
        <el-table-column label="注册时间" width="170">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="260">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-2">
              <el-button type="primary" link size="small" @click="openDetail(row)">
                <el-icon><View /></el-icon>
                详情
              </el-button>
              <el-button type="primary" link size="small" @click="openEdit(row)">
                <el-icon><EditPen /></el-icon>
                编辑
              </el-button>
              <el-button type="success" link size="small" @click="openTagDrawer(row)">
                <el-icon><Collection /></el-icon>
                打标签
              </el-button>
              <el-popconfirm
                :title="row.status === 'banned' ? '解除禁用该会员？' : '确认禁用该会员？'"
                @confirm="toggleStatus(row)"
              >
                <template #reference>
                  <el-button :type="row.status === 'banned' ? 'warning' : 'danger'" link size="small">
                    <el-icon><Lock /></el-icon>
                    {{ row.status === 'banned' ? '解禁' : '禁用' }}
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
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="editForm.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="editForm.phone" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="性别">
          <el-select v-model="editForm.gender" placeholder="请选择性别" clearable>
            <el-option label="未知" value="unknown" />
            <el-option label="男" value="male" />
            <el-option label="女" value="female" />
          </el-select>
        </el-form-item>
        <el-form-item label="等级">
          <el-select v-model="editForm.level" placeholder="请选择等级">
            <el-option v-for="item in levelOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="成长值">
          <el-input-number v-model="editForm.growth_value" :min="0" class="w-full" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="editForm.status">
            <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="来源">
          <el-select v-model="editForm.source">
            <el-option v-for="item in sourceOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="editForm.remark" type="textarea" rows="3" placeholder="可选，管理员备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" :loading="editLoading" @click="submitEdit">
          <template #icon><el-icon><Check /></el-icon></template>
          {{ submitButtonText }}
        </el-button>
      </template>
    </el-drawer>

    <el-drawer v-model="tagDrawerVisible" title="会员标签" size="420px">
      <el-form label-width="90px">
        <el-form-item label="会员">
          <div class="text-base font-medium">{{ currentMember?.nickname || '-' }} (ID: {{ currentMember?.id }})</div>
        </el-form-item>
        <el-form-item label="选择标签">
          <el-select v-model="selectedTags" multiple filterable class="w-full" placeholder="选择标签">
            <el-option v-for="tag in tagOptions" :key="tag.id" :label="tag.name" :value="tag.id">
              <span class="flex items-center gap-2">
                <el-tag :style="{ borderColor: tag.color, color: tag.color }" size="small">{{ tag.name }}</el-tag>
                <small class="text-gray-400">{{ tag.status === 'active' ? '启用' : '停用' }}</small>
              </span>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="tagDrawerVisible = false">取消</el-button>
        <el-button type="primary" :loading="tagLoading" @click="saveTags">
          <template #icon><el-icon><Check /></el-icon></template>
          保存
        </el-button>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import dayjs from 'dayjs'
import { Check, Collection, EditPen, Lock, Plus, Refresh, Search, View } from '@element-plus/icons-vue'
import TagManager from './tag-manager.vue'
import MemberDetail from './detail.vue'
import { memberApi, memberTagApi, type MallMember, type MemberTag } from '~/member/api/member'

defineOptions({ name: 'member:list' })

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

const statusOptions = [
  { label: '正常', value: 'active' },
  { label: '未激活', value: 'inactive' },
  { label: '已禁用', value: 'banned' },
]

const levelOptions = [
  { label: '青铜', value: 'bronze' },
  { label: '白银', value: 'silver' },
  { label: '黄金', value: 'gold' },
  { label: '钻石', value: 'diamond' },
]

const sourceOptions = [
  { label: '微信公众号', value: 'wechat' },
  { label: '微信小程序', value: 'mini_program' },
  { label: 'H5', value: 'h5' },
  { label: '后台导入', value: 'admin' },
]

const statusLabelMap: Record<string, string> = {
  active: '正常',
  inactive: '未激活',
  banned: '已禁用',
}

const statusTagTypeMap: Record<string, 'success' | 'info' | 'danger'> = {
  active: 'success',
  inactive: 'info',
  banned: 'danger',
}

const levelLabelMap: Record<string, string> = {
  bronze: '青铜',
  silver: '白银',
  gold: '黄金',
  diamond: '钻石',
}

const sourceLabelMap: Record<string, string> = {
  wechat: '微信公众号',
  mini_program: '微信小程序',
  h5: 'H5',
  admin: '后台导入',
}

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
    ElMessage.error(error?.message || '加载会员失败')
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
    console.error('加载标签失败', error)
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
})

const editForm = reactive(getDefaultEditForm())

const resetEditForm = () => {
  Object.assign(editForm, getDefaultEditForm())
}

const isCreateMode = ref(false)
const editDrawerTitle = computed(() => (isCreateMode.value ? '新增会员' : '编辑会员'))
const submitButtonText = computed(() => (isCreateMode.value ? '创建' : '保存'))

const editRules: FormRules = {
  nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const openCreate = () => {
  resetEditForm()
  isCreateMode.value = true
  editVisible.value = true
}

const openEdit = (row: MallMember) => {
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
  })
  editVisible.value = true
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
  }
  try {
    if (isCreateMode.value) {
      await memberApi.create(payload)
      ElMessage.success('会员已创建')
    }
    else {
      await memberApi.update(editForm.id, payload)
      ElMessage.success('会员已更新')
    }
    editVisible.value = false
    loadMembers()
  }
  catch (error: any) {
    ElMessage.error(error?.message || (isCreateMode.value ? '创建失败' : '更新失败'))
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
    ElMessage.success('标签已更新')
    tagDrawerVisible.value = false
    currentMember.value = null
    loadMembers()
  }
  catch (error: any) {
    ElMessage.error(error?.message || '更新标签失败')
  }
  finally {
    tagLoading.value = false
  }
}

const toggleStatus = async (row: MallMember) => {
  const nextStatus = row.status === 'banned' ? 'active' : 'banned'
  try {
    await memberApi.updateStatus(row.id, nextStatus)
    ElMessage.success(nextStatus === 'banned' ? '会员已禁用' : '已解除禁用')
    loadMembers()
  }
  catch (error: any) {
    ElMessage.error(error?.message || '操作失败')
  }
}

const tagManagerVisible = ref(false)
</script>
