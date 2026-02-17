import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { GroupBuyOrderSummaryVo } from '~/mall/api/group-buy-order'

import { ElTag, ElImage, ElProgress, ElButton } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { formatYuan } from '@/utils/price'

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
  sold_out: 'warning',
}

export default function getTableColumns(onViewOrders: (row: GroupBuyOrderSummaryVo) => void): MaProTableColumns[] {
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    pending: t('mall.activityStatus.pending'),
    active: t('mall.activityStatus.active'),
    ended: t('mall.activityStatus.ended'),
    cancelled: t('mall.activityStatus.cancelled'),
    sold_out: t('mall.activityStatus.soldOut'),
  }

  return [
    { label: () => 'ID', prop: 'id', width: '70px' },
    {
      label: () => t('mall.groupBuyOrder.activityInfo'),
      prop: 'title',
      minWidth: '220px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <div class="flex items-center gap-2">
          {row.product_image && <ElImage src={row.product_image} style="width: 40px; height: 40px" fit="cover" />}
          <div class="flex-1 min-w-0">
            <div class="font-medium truncate">{row.title}</div>
            <div class="text-xs text-gray-400 truncate">{row.product_name}</div>
          </div>
        </div>
      ),
    },
    {
      label: () => t('mall.common.status'),
      prop: 'status',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <ElTag type={statusTypeMap[row.status] as any} size="small">
          {statusTextMap[row.status] || row.status}
        </ElTag>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.activityTime'),
      prop: 'time',
      width: '180px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <div class="text-xs">
          <div>{t('mall.groupBuyOrder.startLabel')}: {row.start_time}</div>
          <div>{t('mall.groupBuyOrder.endLabel')}: {row.end_time}</div>
        </div>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.grouping'),
      prop: 'group_count',
      width: '100px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <div class="text-xs">
          <div>{t('mall.groupBuyOrder.opened')}: {row.group_count}</div>
          <div class="text-green-600">{t('mall.groupBuyOrder.success')}: {row.success_group_count}</div>
        </div>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.groupBuyerCount'),
      prop: 'group_buyer_count',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <span class="font-semibold">{row.group_buyer_count}</span>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.originalBuyerCount'),
      prop: 'original_buyer_count',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <span class="text-gray-500">{row.original_buyer_count}</span>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.groupBuyerAmount'),
      prop: 'group_buyer_amount',
      width: '110px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <span class="text-red-500 font-semibold">¥{formatYuan(row.group_buyer_amount)}</span>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.stockSales'),
      prop: 'quantity',
      width: '140px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => {
        const total = row.total_quantity || 0
        const sold = row.sold_quantity || 0
        const percent = total > 0 ? Math.round((sold / total) * 100) : 0
        return (
          <div class="w-full">
            <div class="text-xs text-gray-500 mb-1">{sold} / {total}</div>
            <ElProgress percentage={percent} strokeWidth={6} showText={false} />
          </div>
        )
      },
    },
    {
      label: () => t('mall.groupBuyOrder.operationLabel'),
      prop: 'action',
      width: '120px',
      fixed: 'right',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <ElButton type="primary" link size="small" onClick={() => onViewOrders(row)}>
          {t('mall.groupBuyOrder.viewOrders')}
        </ElButton>
      ),
    },
  ]
}
