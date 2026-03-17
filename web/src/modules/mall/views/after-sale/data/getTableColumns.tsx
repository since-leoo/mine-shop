import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { AfterSaleStatus, AfterSaleType, AfterSaleVo } from '~/mall/api/after-sale'

import { ElButton, ElTag } from 'element-plus'

const typeTextMap: Record<AfterSaleType, string> = {
  refund_only: '仅退款',
  return_refund: '退货退款',
  exchange: '换货',
}

const statusTextMap: Record<AfterSaleStatus, string> = {
  pending_review: '待审核',
  waiting_buyer_return: '待买家退货',
  waiting_seller_receive: '待商家收货',
  waiting_refund: '待退款',
  refunding: '退款中',
  waiting_reship: '待补发',
  reshipped: '已补发',
  completed: '已完成',
  closed: '已关闭',
}

const statusTypeMap: Record<AfterSaleStatus, 'warning' | 'success' | 'info' | 'danger'> = {
  pending_review: 'warning',
  waiting_buyer_return: 'info',
  waiting_seller_receive: 'info',
  waiting_refund: 'warning',
  refunding: 'warning',
  waiting_reship: 'info',
  reshipped: 'success',
  completed: 'success',
  closed: 'danger',
}

function formatYuan(amount?: number): string {
  return `￥${((amount ?? 0) / 100).toFixed(2)}`
}

export default function getTableColumns(
  onView: (row: AfterSaleVo) => void,
  onApprove: (row: AfterSaleVo) => void,
  onReject: (row: AfterSaleVo) => void,
  onReceive: (row: AfterSaleVo) => void,
  onRefund: (row: AfterSaleVo) => void,
  onReship: (row: AfterSaleVo) => void,
  onCompleteExchange: (row: AfterSaleVo) => void,
): MaProTableColumns[] {
  return [
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '售后单号', prop: 'after_sale_no', minWidth: '170px' },
    { label: () => '订单号', prop: 'order_no', minWidth: '170px' },
    {
      label: () => '商品信息',
      prop: 'product.productName',
      minWidth: '220px',
      cellRender: (data) => {
        const row = data.row as AfterSaleVo
        return (
          <div class="leading-5">
            <div>{row.product?.productName || '--'}</div>
            <div class="text-xs text-gray-400">{row.product?.skuName || '--'}</div>
          </div>
        )
      },
    },
    { label: () => '会员ID', prop: 'member_id', width: '100px' },
    {
      label: () => '售后类型',
      prop: 'type',
      width: '110px',
      cellRender: (data) => {
        const row = data.row as AfterSaleVo
        return typeTextMap[row.type] || row.type
      },
    },
    {
      label: () => '售后状态',
      prop: 'status',
      width: '130px',
      cellRender: (data) => {
        const row = data.row as AfterSaleVo
        return (
          <ElTag type={statusTypeMap[row.status]} size="small">
            {statusTextMap[row.status] || row.status}
          </ElTag>
        )
      },
    },
    {
      label: () => '申请金额',
      prop: 'apply_amount',
      width: '120px',
      cellRender: (data) => formatYuan((data.row as AfterSaleVo)?.apply_amount),
    },
    { label: () => '申请原因', prop: 'reason', minWidth: '180px' },
    { label: () => '创建时间', prop: 'created_at', width: '170px' },
    {
      label: () => '操作',
      prop: 'action',
      width: '320px',
      fixed: 'right',
      cellRender: (data) => {
        const row = data.row as AfterSaleVo
        return (
          <div class="flex flex-wrap gap-1">
            <ElButton type="primary" link size="small" onClick={() => onView(row)}>
              详情
            </ElButton>
            {row.status === 'pending_review' && (
              <>
                <ElButton type="success" link size="small" onClick={() => onApprove(row)}>
                  通过
                </ElButton>
                <ElButton type="danger" link size="small" onClick={() => onReject(row)}>
                  拒绝
                </ElButton>
              </>
            )}
            {row.status === 'waiting_seller_receive' && (
              <ElButton type="warning" link size="small" onClick={() => onReceive(row)}>
                确认收货
              </ElButton>
            )}
            {row.status === 'waiting_refund' && (
              <ElButton type="success" link size="small" onClick={() => onRefund(row)}>
                确认退款
              </ElButton>
            )}
            {row.status === 'waiting_reship' && (
              <ElButton type="warning" link size="small" onClick={() => onReship(row)}>
                去补发
              </ElButton>
            )}
            {row.status === 'reshipped' && row.type === 'exchange' && (
              <ElButton type="success" link size="small" onClick={() => onCompleteExchange(row)}>
                确认换货完成
              </ElButton>
            )}
          </div>
        )
      },
    },
  ]
}
