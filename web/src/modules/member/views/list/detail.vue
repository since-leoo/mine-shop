<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <el-drawer :model-value="visible" :title="t('member.list.detailTitle')" size="720px" @close="handleClose">
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
              {{ member.nickname || t('member.list.noNickname') }}
              <el-tag size="small">{{ levelLabelMap[member.level || 'bronze'] }}</el-tag>
              <el-tag :type="statusTypeMap[member.status]" size="small">{{ statusLabelMap[member.status] }}</el-tag>
            </div>
            <div class="text-sm text-gray-500">
              {{ t('member.list.idAndRegister', { id: member.id, date: formatDateTime(member.created_at) }) }}
            </div>
          </div>
        </div>
      </el-card>

      <el-descriptions :title="t('member.list.basicInfo')" :column="2" border>
        <el-descriptions-item :label="t('member.list.phoneLabel')">{{ member.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.genderLabel')">{{ genderLabelMap[member.gender || 'unknown'] }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.birthdayLabel')">{{ member.birthday || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.regionLabel')">
          {{ [member.province, member.city, member.district, member.street].filter(Boolean).join(' / ') || '-' }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('member.list.sourceLabel')">{{ sourceLabelMap[member.source || 'wechat'] }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.lastLoginLabel')">{{ member.last_login_at ? formatDateTime(member.last_login_at) : t('member.list.noLogin') }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.growthLabel')">{{ member.growth_value ?? 0 }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.remarkLabel')">{{ member.remark || '-' }}</el-descriptions-item>
      </el-descriptions>

      <el-descriptions :title="t('member.list.assetInfo')" :column="4" border>
        <el-descriptions-item :label="t('member.list.balanceLabel')">¥{{ formatYuan(member.wallet?.balance) }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.frozenLabel')">¥{{ formatYuan(member.wallet?.frozen_balance) }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.totalRechargeLabel')">¥{{ formatYuan(member.wallet?.total_recharge) }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.totalConsumeLabel')">¥{{ formatYuan(member.wallet?.total_consume) }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.pointsBalanceLabel')">{{ member.points_wallet?.balance ?? member.points_balance ?? 0 }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.list.totalPointsLabel')">{{ member.points_wallet?.total_recharge ?? member.points_total ?? 0 }}</el-descriptions-item>
      </el-descriptions>

      <el-card shadow="never">
        <template #header>
          <div class="flex items-center justify-between">
            <span>{{ t('member.list.tagsTitle') }}</span>
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
        <el-empty v-else :description="t('member.list.noTags')" />
      </el-card>

      <el-card v-if="member.addresses?.length" shadow="never">
        <template #header>
          <div class="flex items-center justify-between">
            <span>{{ t('member.list.addressTitle') }}</span>
          </div>
        </template>
        <el-timeline>
          <el-timeline-item v-for="address in member.addresses" :key="address.id" :timestamp="formatDateTime(address.created_at)" placement="top">
            <el-card shadow="never">
              <div class="flex items-center gap-3">
                <el-tag v-if="address.is_default" size="small" type="success">{{ t('member.list.defaultTag') }}</el-tag>
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
      <el-button @click="handleClose">{{ t('mall.common.close') }}</el-button>
    </template>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'
import { memberApi, type MemberVo } from '~/member/api/member'
import { formatYuan } from '@/utils/price'

defineOptions({ name: 'MemberDetailDrawer' })

const { t } = useI18n()

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
  unknown: t('member.list.genderUnknown'),
  male: t('member.list.genderMale'),
  female: t('member.list.genderFemale'),
}

const levelLabelMap: Record<string, string> = {
  bronze: t('member.list.levelBronze'),
  silver: t('member.list.levelSilver'),
  gold: t('member.list.levelGold'),
  diamond: t('member.list.levelDiamond'),
}

const sourceLabelMap: Record<string, string> = {
  wechat: t('member.list.sourceWechat'),
  mini_program: t('member.list.sourceMiniProgram'),
  h5: t('member.list.sourceH5'),
  admin: t('member.list.sourceAdmin'),
}

const statusLabelMap: Record<string, string> = {
  active: t('member.list.statusActive'),
  inactive: t('member.list.statusInactive'),
  banned: t('member.list.statusBanned'),
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
    ElMessage.error(error?.message || t('member.list.loadDetailFailed'))
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