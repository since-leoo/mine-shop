<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<template>
  <el-drawer :model-value="visible" title="订单详情" size="720px" @close="handleClose">
    <div v-if="order" class="space-y-6">
      <el-descriptions title="基本信息" :column="2" border>
        <el-descriptions-item label="订单号">
          {{ order.order_no }}
        </el-descriptions-item>
        <el-descriptions-item label="订单状态">
          <el-tag :type="statusTypeMap[order.status || 'pending']" size="small">
            {{ statusLabelMap[order.status || 'pending'] }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="下单时间">
          {{ formatTime(order.created_at) }}
        </el-descriptions-item>
        <el-descriptions-item label="支付时间">
          {{ order.pay_time ? formatTime(order.pay_time) : '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="发货时间">
          {{ order.shipped_at ? formatTime(order.shipped_at) : '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="完成时间">
          {{ order.completed_at ? formatTime(order.completed_at) : '-' }}
        </el-descriptions-item>
      </el-descriptions>

      <el-descriptions title="买家信息" :column="2" border>
        <el-descriptions-item label="买家昵称">
          {{ order.address?.name || order.member?.nickname || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="联系电话">
          {{ order.address?.phone || order.member?.phone || '-' }}
        </el-descriptions-item>
      </el-descriptions>

      <el-descriptions v-if="order.address" title="收货地址" :column="1" border>
        <el-descriptions-item label="收货人">
          {{ order.address.name }} {{ order.address.phone }}
        </el-descriptions-item>
        <el-descriptions-item label="收货地址">
          {{ order.address.province }} {{ order.address.city }} {{ order.address.district }} {{ order.address.detail || order.address.address }}
        </el-descriptions-item>
      </el-descriptions>

      <div>
        <div class="text-base font-medium mb-3">商品信息</div>
        <el-table :data="order.items || []" size="small" border>
          <el-table-column label="商品" min-width="220">
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
          <el-table-column label="单价" width="100" align="right">
            <template #default="{ row }">
              ¥{{ formatYuan(row.unit_price) }}
            </template>
          </el-table-column>
          <el-table-column label="数量" prop="quantity" width="80" align="center" />
          <el-table-column label="小计" width="100" align="right">
            <template #default="{ row }">
              ¥{{ formatYuan(row.total_price) }}
            </template>
          </el-table-column>
        </el-table>
      </div>

      <el-descriptions title="金额信息" :column="2" border>
        <el-descriptions-item label="商品总额">
          ¥{{ formatYuan(order.total_amount) }}
        </el-descriptions-item>
        <el-descriptions-item label="运费">
          ¥{{ formatYuan(order.shipping_fee) }}
        </el-descriptions-item>
        <el-descriptions-item label="优惠金额">
          -¥{{ formatYuan(order.discount_amount) }}
        </el-descriptions-item>
        <el-descriptions-item label="实付金额">
          <span class="text-red-500 font-semibold">¥{{ formatYuan(order.pay_amount) }}</span>
        </el-descriptions-item>
      </el-descriptions>

      <el-descriptions v-if="order.seller_remark || order.buyer_remark" title="备注信息" :column="1" border>
        <el-descriptions-item v-if="order.buyer_remark" label="买家备注">
          {{ order.buyer_remark }}
        </el-descriptions-item>
        <el-descriptions-item v-if="order.seller_remark" label="卖家备注">
          {{ order.seller_remark }}
        </el-descriptions-item>
      </el-descriptions>
    </div>
    <template #footer>
      <el-button @click="handleClose">关闭</el-button>
    </template>
  </el-drawer>
</template>

<script setup lang="ts">
import { watch } from 'vue'
import type { OrderVo } from '~/mall/api/order'
import dayjs from 'dayjs'
import { formatYuan } from '@/utils/price'

defineOptions({ name: 'MallOrderDetailDrawer' })

const props = defineProps<{
  visible: boolean
  order: OrderVo | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
}>()

const statusLabelMap: Record<string, string> = {
  pending: '待付款',
  paid: '已付款',
  shipped: '已发货',
  completed: '已完成',
  cancelled: '已取消',
}

const statusTypeMap: Record<string, string> = {
  pending: 'warning',
  paid: 'primary',
  shipped: 'success',
  completed: 'success',
  cancelled: 'info',
}

const formatTime = (time?: string) => (time ? dayjs(time).format('YYYY-MM-DD HH:mm:ss') : '-')

const handleClose = () => emit('update:visible', false)

watch(
  () => props.visible,
  (visible) => {
    if (!visible) {
      emit('update:visible', false)
    }
  },
)
</script>
