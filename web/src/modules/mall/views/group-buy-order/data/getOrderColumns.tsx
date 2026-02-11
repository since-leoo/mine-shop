import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { GroupBuyOrderDetailVo } from '~/mall/api/group-buy-order'

import { ElTag } from 'element-plus'
import { formatYuan } from '@/utils/price'

const orderStatusMap: Record<string, { text: string, type: string }> = {
  pending: { text: '待付款', type: 'warning' },
  paid: { text: '已付款', type: 'success' },
  grouped: { text: '已成团', type: 'success' },
  cancelled: { text: '已取消', type: 'info' },
  failed: { text: '拼团失败', type: 'danger' },
}

export default function getOrderColumns(): MaProTableColumns[] {
  return [
    { label: () => '团号', prop: 'group_no', width: '180px' },
    {
      label: () => '团长',
      prop: 'is_leader',
      width: '60px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) =>
        row.is_leader ? <ElTag type="warning" size="small">团长</ElTag> : <span class="text-gray-400">团员</span>,
    },
    { label: () => '会员', prop: 'member_nickname', width: '100px' },
    { label: () => '手机号', prop: 'member_phone', width: '120px' },
    { label: () => '数量', prop: 'quantity', width: '60px' },
    {
      label: () => '团购价',
      prop: 'group_price',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) => (
        <span class="text-red-500">¥{formatYuan(row.group_price)}</span>
      ),
    },
    {
      label: () => '实付金额',
      prop: 'total_amount',
      width: '100px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) => (
        <span class="font-semibold">¥{formatYuan(row.total_amount)}</span>
      ),
    },
    {
      label: () => '拼团状态',
      prop: 'status',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) => {
        const s = orderStatusMap[row.status] || { text: row.status, type: 'info' }
        return <ElTag type={s.type as any} size="small">{s.text}</ElTag>
      },
    },
    { label: () => '关联订单', prop: 'order_no', width: '180px' },
    { label: () => '参团时间', prop: 'join_time', width: '160px' },
    { label: () => '过期时间', prop: 'expire_time', width: '160px' },
  ]
}
