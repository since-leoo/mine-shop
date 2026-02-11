import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { GroupBuyOrderSummaryVo } from '~/mall/api/group-buy-order'

import { ElTag, ElImage, ElProgress, ElButton } from 'element-plus'
import { formatYuan } from '@/utils/price'

const statusTextMap: Record<string, string> = {
  pending: '待开始',
  active: '进行中',
  ended: '已结束',
  cancelled: '已取消',
  sold_out: '已售罄',
}

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
  sold_out: 'warning',
}

export default function getTableColumns(onViewOrders: (row: GroupBuyOrderSummaryVo) => void): MaProTableColumns[] {
  return [
    { label: () => 'ID', prop: 'id', width: '70px' },
    {
      label: () => '活动信息',
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
      label: () => '状态',
      prop: 'status',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <ElTag type={statusTypeMap[row.status] as any} size="small">
          {statusTextMap[row.status] || row.status}
        </ElTag>
      ),
    },
    {
      label: () => '活动时间',
      prop: 'time',
      width: '180px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <div class="text-xs">
          <div>开始: {row.start_time}</div>
          <div>结束: {row.end_time}</div>
        </div>
      ),
    },
    {
      label: () => '成团',
      prop: 'group_count',
      width: '100px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <div class="text-xs">
          <div>开团: {row.group_count}</div>
          <div class="text-green-600">成功: {row.success_group_count}</div>
        </div>
      ),
    },
    {
      label: () => '参团人数',
      prop: 'group_buyer_count',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <span class="font-semibold">{row.group_buyer_count}</span>
      ),
    },
    {
      label: () => '原价购买',
      prop: 'original_buyer_count',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <span class="text-gray-500">{row.original_buyer_count}</span>
      ),
    },
    {
      label: () => '参团总金额',
      prop: 'group_buyer_amount',
      width: '110px',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <span class="text-red-500 font-semibold">¥{formatYuan(row.group_buyer_amount)}</span>
      ),
    },
    {
      label: () => '库存/销量',
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
      label: () => '操作',
      prop: 'action',
      width: '120px',
      fixed: 'right',
      cellRender: ({ row }: { row: GroupBuyOrderSummaryVo }) => (
        <ElButton type="primary" link size="small" onClick={() => onViewOrders(row)}>
          查看拼团订单
        </ElButton>
      ),
    },
  ]
}
