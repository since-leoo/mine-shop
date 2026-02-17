<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { ReviewVo } from '~/mall/api/review'

import { reviewPage, reviewReply, reviewStats } from '~/mall/api/review'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'mall:review' })

const { t } = useI18n()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const msg = useMessage()
const statsData = ref({ today_reviews: 0, pending_reviews: 0, total_reviews: 0, average_rating: 0 })

// 回复弹窗
const replyDialogVisible = ref(false)
const replyRow = ref<ReviewVo | null>(null)
const replyContent = ref('')
const replyLoading = ref(false)

function onReply(row: ReviewVo) {
  replyRow.value = row
  replyContent.value = row.admin_reply || ''
  replyDialogVisible.value = true
}

async function submitReply() {
  if (!replyContent.value.trim()) {
    msg.warning(t('mall.review.replyEmpty'))
    return
  }
  replyLoading.value = true
  try {
    const res = await reviewReply(replyRow.value!.id!, { content: replyContent.value })
    if (res.code === ResultCode.SUCCESS) {
      msg.success(t('mall.review.replySuccess'))
      replyDialogVisible.value = false
      proTableRef.value.refresh()
    }
  }
  catch (err: any) {
    msg.alertError(err)
  }
  finally {
    replyLoading.value = false
  }
}

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 140,
  header: {
    mainTitle: () => t('mall.review.pageTitle'),
    subTitle: () => t('mall.review.pageSubtitle'),
  },
  searchOptions: {
    fold: false,
    text: {
      searchBtn: () => t('mall.search'),
      resetBtn: () => t('mall.reset'),
    },
  },
  searchFormOptions: { labelWidth: '100px' },
  requestOptions: { api: reviewPage },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(onReply, proTableRef),
})

async function loadStats() {
  try {
    const res = await reviewStats()
    statsData.value = res.data
  }
  catch {}
}

onMounted(() => {
  loadStats()
})
</script>

<template>
  <div class="mine-layout pt-3">
    <el-row :gutter="12" class="mb-3 px-3">
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="stats-card slate">
            <div>
              <div class="stats-label">{{ t('mall.review.totalReviews') }}</div>
              <div class="stats-value">{{ statsData.total_reviews }}</div>
            </div>
            <ma-svg-icon name="ph:chat-circle-text" size="24" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="stats-card amber">
            <div>
              <div class="stats-label">{{ t('mall.review.pendingReview') }}</div>
              <div class="stats-value">{{ statsData.pending_reviews }}</div>
            </div>
            <ma-svg-icon name="ph:clock" size="24" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="stats-card emerald">
            <div>
              <div class="stats-label">{{ t('mall.review.newToday') }}</div>
              <div class="stats-value">{{ statsData.today_reviews }}</div>
            </div>
            <ma-svg-icon name="ph:plus-circle" size="24" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="border-0">
          <div class="stats-card blue">
            <div>
              <div class="stats-label">{{ t('mall.review.avgRating') }}</div>
              <div class="stats-value">{{ statsData.average_rating }}</div>
            </div>
            <ma-svg-icon name="ph:star" size="24" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #empty>
        <el-empty :description="t('mall.review.emptyData')" />
      </template>
    </MaProTable>

    <!-- 回复弹窗 -->
    <el-dialog
      v-model="replyDialogVisible"
      :title="replyRow?.admin_reply ? t('mall.review.viewReply') : t('mall.review.replyReview')"
      width="500px"
      destroy-on-close
    >
      <div class="mb-4">
        <div class="text-sm text-gray-500 mb-1">{{ t('mall.review.reviewContent') }}</div>
        <div class="text-sm">{{ replyRow?.content }}</div>
      </div>
      <el-input
        v-model="replyContent"
        type="textarea"
        :rows="4"
        :placeholder="t('mall.review.replyPlaceholder')"
        maxlength="500"
        show-word-limit
      />
      <template #footer>
        <el-button @click="replyDialogVisible = false">{{ t('mall.review.cancelReply') }}</el-button>
        <el-button type="primary" :loading="replyLoading" @click="submitReply">
          {{ t('mall.review.confirmReply') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.stats-card {
  --stats-card-surface: var(--el-bg-color);
  --stats-card-bg: color-mix(in srgb, var(--stats-card-surface) 92%, transparent);
  --stats-card-color: var(--el-text-color-primary);
  @apply flex items-center justify-between rounded-xl px-4 py-3;
  background: var(--stats-card-bg);
  color: var(--stats-card-color);
  border: 1px solid color-mix(in srgb, var(--el-border-color) 60%, transparent);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  transition: background 0.2s ease, color 0.2s ease, border 0.2s ease, box-shadow 0.2s ease;

  &.slate {
    --stats-card-bg: color-mix(in srgb, #1e293b 18%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-text-color-primary) 70%, #0f172a 30%);
  }

  &.amber {
    --stats-card-bg: color-mix(in srgb, #f59e0b 20%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, #92400e 55%, var(--el-text-color-primary) 45%);
  }

  &.emerald {
    --stats-card-bg: color-mix(in srgb, #10b981 20%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, #065f46 55%, var(--el-text-color-primary) 45%);
  }

  &.blue {
    --stats-card-bg: color-mix(in srgb, #3b82f6 20%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, #1e3a5f 55%, var(--el-text-color-primary) 45%);
  }

  .stats-label {
    font-size: 12px;
    color: color-mix(in srgb, var(--stats-card-color) 55%, var(--el-text-color-secondary) 45%);
  }

  .stats-value {
    margin-top: 8px;
    font-size: 26px;
    font-weight: 600;
  }
}

:global(html.dark) {
  .stats-card {
    --stats-card-surface: var(--el-bg-color-overlay);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
  }

  .stats-card.slate {
    --stats-card-bg: color-mix(in srgb, #1e293b 55%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 85%, #1e293b 15%);
  }

  .stats-card.amber {
    --stats-card-bg: color-mix(in srgb, #f59e0b 45%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 80%, #fef3c7 20%);
  }

  .stats-card.emerald {
    --stats-card-bg: color-mix(in srgb, #10b981 45%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 80%, #d1fae5 20%);
  }

  .stats-card.blue {
    --stats-card-bg: color-mix(in srgb, #3b82f6 45%, var(--stats-card-surface));
    --stats-card-color: color-mix(in srgb, var(--el-color-white) 80%, #dbeafe 20%);
  }

  .stats-card .stats-label {
    color: color-mix(in srgb, var(--el-color-white) 75%, transparent);
  }
}
</style>
