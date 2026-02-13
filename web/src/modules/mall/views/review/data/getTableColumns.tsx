import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { ReviewVo } from '~/mall/api/review'

import { ElTag, ElButton, ElRate } from 'element-plus'
import { reviewApprove, reviewReject } from '~/mall/api/review'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

const statusTextMap: Record<string, string> = {
  pending: '待审核',
  approved: '已通过',
  rejected: '已拒绝',
}

const statusTypeMap: Record<string, string> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

export default function getTableColumns(
  onReply: (row: ReviewVo) => void,
  tableRef: { value: MaProTableExpose | undefined },
): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { label: () => 'ID', prop: 'id', width: '70px' },
    {
      label: () => '商品名称',
      prop: 'product_name',
      minWidth: '160px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <span class="truncate">{row.product_name || '--'}</span>
      ),
    },
    {
      label: () => '用户',
      prop: 'member_nickname',
      width: '120px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <span>{row.is_anonymous ? '匿名用户' : (row.member_nickname || '--')}</span>
      ),
    },
    {
      label: () => '评分',
      prop: 'rating',
      width: '150px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <ElRate modelValue={row.rating} disabled size="small" />
      ),
    },
    {
      label: () => '评价内容',
      prop: 'content',
      minWidth: '200px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <span class="truncate" title={row.content}>
          {row.content && row.content.length > 50 ? `${row.content.slice(0, 50)}...` : row.content}
        </span>
      ),
    },
    {
      label: () => '状态',
      prop: 'status',
      width: '90px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <ElTag type={statusTypeMap[row.status!] as any} size="small">
          {statusTextMap[row.status!] || row.status}
        </ElTag>
      ),
    },
    {
      label: () => '创建时间',
      prop: 'created_at',
      width: '170px',
    },
    {
      label: () => '操作',
      prop: 'action',
      width: '200px',
      fixed: 'right',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <div class="flex gap-1">
          {row.status === 'pending' && (
            <>
              <ElButton
                type="success"
                link
                size="small"
                onClick={() => {
                  msg.confirm('确定通过该评价吗？').then(async () => {
                    const res = await reviewApprove(row.id!)
                    if (res.code === ResultCode.SUCCESS) {
                      msg.success('审核通过')
                      tableRef.value?.refresh()
                    }
                  })
                }}
              >
                通过
              </ElButton>
              <ElButton
                type="danger"
                link
                size="small"
                onClick={() => {
                  msg.confirm('确定拒绝该评价吗？').then(async () => {
                    const res = await reviewReject(row.id!)
                    if (res.code === ResultCode.SUCCESS) {
                      msg.success('已拒绝')
                      tableRef.value?.refresh()
                    }
                  })
                }}
              >
                拒绝
              </ElButton>
            </>
          )}
          <ElButton
            type="primary"
            link
            size="small"
            onClick={() => onReply(row)}
          >
            {row.admin_reply ? '查看回复' : '回复'}
          </ElButton>
        </div>
      ),
    },
  ]
}
