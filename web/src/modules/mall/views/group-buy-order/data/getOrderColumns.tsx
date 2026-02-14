import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { GroupBuyOrderDetailVo } from '~/mall/api/group-buy-order'

import { ElTag } from 'element-plus'
import { formatYuan } from '@/utils/price'
import { useI18n } from 'vue-i18n'

export default function getOrderColumns(): MaProTableColumns[] {
  const { t } = useI18n()

  const orderStatusMap: Record<string, { text: string, type: string }> = {
    pending: { text: t('mall.orderStatus.pending'), type: 'warning' },
    paid: { text: t('mall.orderStatus.paid'), type: 'success' },
    grouped: { text: t('mall.orderStatus.grouped'), type: 'success' },
    cancelled: { text: t('mall.orderStatus.cancelled'), type: 'info' },
    failed: { text: t('mall.orderStatus.failed'), type: 'danger' },
  }

  return [
    { label: () => t('mall.groupBuyOrder.groupNo'), prop: 'group_no', width: '180px' },
    {
      label: () => t('mall.groupBuyOrder.leader'),
      prop: 'is_leader',
      width: '60px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) =>
        row.is_leader ? <ElTag type="warning" size="small">{t('mall.groupBuyOrder.leaderTag')}</ElTag> : <span class="text-gray-400">{t('mall.groupBuyOrder.member')}</span>,
    },
    { label: () => t('mall.groupBuyOrder.memberName'), prop: 'member_nickname', width: '100px' },
    { label: () => t('mall.groupBuyOrder.phone'), prop: 'member_phone', width: '120px' },
    { label: () => t('mall.groupBuyOrder.quantity'), prop: 'quantity', width: '60px' },
    {
      label: () => t('mall.groupBuyOrder.groupPrice'),
      prop: 'group_price',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) => (
        <span class="text-red-500">¥{formatYuan(row.group_price)}</span>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.paidAmount'),
      prop: 'total_amount',
      width: '100px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) => (
        <span class="font-semibold">¥{formatYuan(row.total_amount)}</span>
      ),
    },
    {
      label: () => t('mall.groupBuyOrder.groupStatus'),
      prop: 'status',
      width: '90px',
      cellRender: ({ row }: { row: GroupBuyOrderDetailVo }) => {
        const s = orderStatusMap[row.status] || { text: row.status, type: 'info' }
        return <ElTag type={s.type as any} size="small">{s.text}</ElTag>
      },
    },
    { label: () => t('mall.groupBuyOrder.relatedOrder'), prop: 'order_no', width: '180px' },
    { label: () => t('mall.groupBuyOrder.joinTime'), prop: 'join_time', width: '160px' },
    { label: () => t('mall.groupBuyOrder.expireTime'), prop: 'expire_time', width: '160px' },
  ]
}
