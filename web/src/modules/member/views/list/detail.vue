<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <el-drawer :model-value="visible" title="会员详情" size="720px" @close="handleClose">
    <div v-if="loading" class="flex items-center justify-center py-10">
      <el-icon class="is-loading" :size="32"><Loading /></el-icon>
    </div>
    <div v-else-if="member" class="space-y-6">
      <el-card shadow="never">
        <div class="flex items-center gap-4">
          <el-avatar :size="72" :src="member.avatar">
            {{ member.nickname?.slice(0, 1) || 'U' }}
          </el-avatar>
          <div>
            <div class="text-lg font-semibold flex items-center gap-2">
              {{ member.nickname || '未设置昵称' }}
              <el-tag size="small">{{ levelLabelMap[member.level || 'bronze'] }}</el-tag>
              <el-tag :type="statusTypeMap[member.status]" size="small">{{ statusLabelMap[member.status] }}</el-tag>
            </div>
            <div class="text-sm text-gray-500">
              ID：{{ member.id }} · 注册于 {{ formatDateTime(member.created_at) }}
            </div>
          </div>
        </div>
      </el-card>

      <el-descriptions title="基础信息" :column="2" border>
        <el-descriptions-item label="手机号">{{ member.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ genderLabelMap[member.gender || 'unknown'] }}</el-descriptions-item>
        <el-descriptions-item label="生日">{{ member.birthday || '-' }}</el-descriptions-item>
        <el-descriptions-item label="地区">
          {{ [member.province, member.city, member.district, member.street].filter(Boolean).join(' / ') || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="来源渠道">{{ sourceLabelMap[member.source || 'wechat'] }}</el-descriptions-item>
        <el-descriptions-item label="最近登录">{{ member.last_login_at ? formatDateTime(member.last_login_at) : '暂无' }}</el-descriptions-item>
        <el-descriptions-item label="成长值">{{ member.growth_value ?? 0 }}</el-descriptions-item>
        <el-descriptions-item label="备注">{{ member.remark || '-' }}</el-descriptions-item>
      </el-descriptions>

      <el-descriptions title="资产信息" :column="4" border>
        <el-descriptions-item label="账户余额">¥{{ formatYuan(member.wallet?.balance) }}</el-descriptions-item>
        <el-descriptions-item label="冻结金额">¥{{ formatYuan(member.wallet?.frozen_balance) }}</el-descriptions-item>
        <el-descriptions-item label="累计充值">¥{{ formatYuan(member.wallet?.total_recharge) }}</el-descriptions-item>
        <el-descriptions-item label="累计消费">¥{{ formatYuan(member.wallet?.total_consume) }}</el-descriptions-item>
        <el-descriptions-item label="积分余额">{{ member.points_wallet?.balance ?? member.points_balance ?? 0 }}</el-descriptions-item>
        <el-descriptions-item label="累计积分">{{ member.points_wallet?.total_recharge ?? member.points_total ?? 0 }}</el-descriptions-item>
      </el-descriptions>

      <el-card shadow="never">
        <template #header>
          <div class="flex items-center justify-between">
            <span>标签</span>
          </div>
        </template>
        <div v-if="member.tags?.length" class="flex flex-wrap gap-2">
          <el-tag
            v-for="tag in member.tags"
            :key="tag.id"
            :type="tag.status === 'active' ? 'success' : 'info'"
            :style="{ borderColor: tag.color, color: tag.color }"
          >
            {{ tag.name }}
          </el-tag>
        </div>
        <el-empty v-else description="尚未设置标签" />
      </el-card>

      <el-card v-if="member.addresses?.length" shadow="never">
        <template #header>
          <div class="flex items-center justify-between">
            <span>收货地址</span>
          </div>
        </template>
        <el-timeline>
          <el-timeline-item v-for="address in member.addresses" :key="address.id" :timestamp="formatDateTime(address.created_at)" placement="top">
            <el-card shadow="never">
              <div class="flex items-center gap-3">
                <el-tag v-if="address.is_default" size="small" type="success">默认</el-tag>
                <span class="font-medium">{{ address.name }} {{ address.phone }}</span>
              </div>
              <div class="text-sm text-gray-500 mt-1">
                {{ address.province }} {{ address.city }} {{ address.district }} {{ address.detail }}
              </div>
            </el-card>
          </el-timeline-item>
        </el-timeline>
      </el-card>
    </div>
    <template #footer>
      <el-button @click="handleClose">关闭</el-button>
    </template>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import dayjs from 'dayjs'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'
import { memberApi, type MemberVo } from '~/member/api/member'
import { formatYuan } from '@/utils/price'

defineOptions({ name: 'MemberDetailDrawer' })

const props = defineProps<{
  visible: boolean
  memberId: number | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
}>()

const member = ref<MemberVo | null>(null)
const loading = ref(false)

const genderLabelMap: Record<string, string> = {
  unknown: '未知',
  male: '男',
  female: '女',
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

const statusLabelMap: Record<string, string> = {
  active: '正常',
  inactive: '未激活',
  banned: '已禁用',
}

const statusTypeMap: Record<string, 'success' | 'warning' | 'danger'> = {
  active: 'success',
  inactive: 'warning',
  banned: 'danger',
}

const formatDateTime = (value?: string | null) => (value ? dayjs(value).format('YYYY-MM-DD HH:mm') : '-')

const loadDetail = async () => {
  if (!props.memberId) {
    member.value = null
    return
  }
  loading.value = true
  try {
    const res = await memberApi.detail(props.memberId)
    member.value = res.data
  }
  catch (error: any) {
    ElMessage.error(error?.message || '加载会员详情失败')
  }
  finally {
    loading.value = false
  }
}

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      loadDetail()
    }
  },
  { immediate: false },
)

watch(
  () => props.memberId,
  (id) => {
    if (props.visible && id) {
      loadDetail()
    }
  },
)

const handleClose = () => emit('update:visible', false)
</script>
