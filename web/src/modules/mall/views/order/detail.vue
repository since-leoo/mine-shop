<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<template>
  <el-drawer :model-value="visible" :title="t('mall.order.detailTitle')" size="720px" @close="handleClose">
    <div v-if="order" class="space-y-6">
      <el-descriptions :title="t('mall.order.basicInfo')" :column="2" border>
        <el-descriptions-item :label="t('mall.order.orderNo')">
          {{ order.order_no }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.orderStatus')">
          <el-tag :type="statusTypeMap[order.status || 'pending']" size="small">
            {{ statusLabelMap[order.status || 'pending'] }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.orderTime')">
          {{ formatTime(order.created_at) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.payTimeLabel')">
          {{ order.pay_time ? formatTime(order.pay_time) : '-' }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.shipTimeLabel')">
          {{ order.shipped_at ? formatTime(order.shipped_at) : '-' }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.completeTimeLabel')">
          {{ order.completed_at ? formatTime(order.completed_at) : '-' }}
        </el-descriptions-item>
      </el-descriptions>

      <el-descriptions :title="t('mall.order.buyerInfo')" :column="2" border>
        <el-descriptions-item :label="t('mall.order.buyerNickname')">
          {{ order.address?.name || order.member?.nickname || '-' }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.contactPhone')">
          {{ order.address?.phone || order.member?.phone || '-' }}
        </el-descriptions-item>
      </el-descriptions>

      <el-descriptions v-if="order.address" :title="t('mall.order.shippingAddress')" :column="1" border>
        <el-descriptions-item :label="t('mall.order.receiver')">
          {{ order.address.name }} {{ order.address.phone }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.addressDetail')">
          {{ order.address.province }} {{ order.address.city }} {{ order.address.district }} {{ order.address.detail || order.address.address }}
        </el-descriptions-item>
      </el-descriptions>

      <div>
        <div class="text-base font-medium mb-3">{{ t('mall.order.productInfoTitle') }}</div>
        <el-table :data="order.items || []" size="small" border>
          <el-table-column :label="t('mall.order.productLabel')" min-width="220">
            <template #default="{ row }">
              <div class="flex items-center gap-2">
                <el-image :src="row.product_image" fit="cover" class="w-12 h-12 rounded" />
                <div class="text-left">
                  <div class="text-sm">{{ row.product_name }}</div>
                  <div v-if="row.sku_name" class="text-xs text-gray-500">{{ row.sku_name }}</div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column :label="t('mall.order.unitPrice')" width="100" align="right">
            <template #default="{ row }">
              ¥{{ formatYuan(row.unit_price) }}
            </template>
          </el-table-column>
          <el-table-column :label="t('mall.order.quantity')" prop="quantity" width="80" align="center" />
          <el-table-column :label="t('mall.order.subtotal')" width="100" align="right">
            <template #default="{ row }">
              ¥{{ formatYuan(row.total_price) }}
            </template>
          </el-table-column>
        </el-table>
      </div>

      <el-descriptions :title="t('mall.order.amountInfo')" :column="2" border>
        <el-descriptions-item :label="t('mall.order.totalAmount')">
          ¥{{ formatYuan(order.total_amount) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.shippingFee')">
          ¥{{ formatYuan(order.shipping_fee) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.discountAmount')">
          -¥{{ formatYuan(order.discount_amount) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('mall.order.paidAmount')">
          <span class="text-red-500 font-semibold">¥{{ formatYuan(order.pay_amount) }}</span>
        </el-descriptions-item>
      </el-descriptions>

      <el-descriptions v-if="order.seller_remark || order.buyer_remark" :title="t('mall.order.remarkInfo')" :column="1" border>
        <el-descriptions-item v-if="order.buyer_remark" :label="t('mall.order.buyerRemark')">
          {{ order.buyer_remark }}
        </el-descriptions-item>
        <el-descriptions-item v-if="order.seller_remark" :label="t('mall.order.sellerRemark')">
          {{ order.seller_remark }}
        </el-descriptions-item>
      </el-descriptions>

      <!-- 评价信息 -->
      <div>
        <div class="text-base font-medium mb-3">{{ t('mall.order.reviewInfo') }}</div>
        <div v-if="reviewLoading" v-loading="true" class="py-6" />
        <el-empty v-else-if="!reviews.length" :description="t('mall.order.noReview')" :image-size="60" />
        <el-table v-else :data="reviews" size="small" border>
          <el-table-column :label="t('mall.order.reviewRating')" width="100" align="center">
            <template #default="{ row }">
              <el-rate :model-value="row.rating" disabled size="small" />
            </template>
          </el-table-column>
          <el-table-column :label="t('mall.order.reviewContent')" min-width="200" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.content }}
            </template>
          </el-table-column>
          <el-table-column :label="t('mall.order.reviewStatus')" width="90" align="center">
            <template #default="{ row }">
              <el-tag :type="reviewStatusType[row.status || 'pending']" size="small">
                {{ reviewStatusLabel[row.status || 'pending'] }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column :label="t('mall.order.adminReply')" min-width="160" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.admin_reply || '-' }}
            </template>
          </el-table-column>
          <el-table-column :label="t('mall.order.reviewTime')" width="160" align="center">
            <template #default="{ row }">
              {{ formatTime(row.created_at) }}
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>
    <template #footer>
      <el-button @click="handleClose">{{ t('mall.order.closeButton') }}</el-button>
    </template>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { OrderVo } from '~/mall/api/order'
import type { ReviewVo } from '~/mall/api/review'
import { reviewsByOrder } from '~/mall/api/review'
import dayjs from 'dayjs'
import { formatYuan } from '@/utils/price'

defineOptions({ name: 'MallOrderDetailDrawer' })

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  order: OrderVo | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
}>()

const reviews = ref<ReviewVo[]>([])
const reviewLoading = ref(false)

const statusLabelMap: Record<string, string> = {
  pending: t('mall.order.statusPending'),
  paid: t('mall.order.statusPaid'),
  shipped: t('mall.order.statusShipped'),
  completed: t('mall.order.statusCompleted'),
  cancelled: t('mall.order.statusCancelled'),
}

const statusTypeMap: Record<string, string> = {
  pending: 'warning',
  paid: 'primary',
  shipped: 'success',
  completed: 'success',
  cancelled: 'info',
}

const reviewStatusLabel: Record<string, string> = {
  pending: t('mall.review.statusPending'),
  approved: t('mall.review.statusApproved'),
  rejected: t('mall.review.statusRejected'),
}

const reviewStatusType: Record<string, string> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const formatTime = (time?: string) => (time ? dayjs(time).format('YYYY-MM-DD HH:mm:ss') : '-')

const handleClose = () => emit('update:visible', false)

async function loadReviews(orderId: number) {
  reviewLoading.value = true
  try {
    const res = await reviewsByOrder(orderId)
    reviews.value = res.data ?? []
  }
  catch {
    reviews.value = []
  }
  finally {
    reviewLoading.value = false
  }
}

watch(
  () => props.visible,
  (visible) => {
    if (visible && props.order?.id) {
      loadReviews(props.order.id)
    }
    else {
      reviews.value = []
    }
    if (!visible) {
      emit('update:visible', false)
    }
  },
)
</script>