import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { ReviewVo } from '~/mall/api/review'

import { ElTag, ElButton, ElRate } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { reviewApprove, reviewReject } from '~/mall/api/review'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

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
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    pending: t('mall.review.statusPending'),
    approved: t('mall.review.statusApproved'),
    rejected: t('mall.review.statusRejected'),
  }

  return [
    { label: () => 'ID', prop: 'id', width: '70px' },
    {
      label: () => t('mall.review.productName'),
      prop: 'product_name',
      minWidth: '160px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <span class="truncate">{row.product_name || '--'}</span>
      ),
    },
    {
      label: () => t('mall.review.user'),
      prop: 'member_nickname',
      width: '120px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <span>{row.is_anonymous ? t('mall.review.anonymous') : (row.member_nickname || '--')}</span>
      ),
    },
    {
      label: () => t('mall.review.rating'),
      prop: 'rating',
      width: '150px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <ElRate modelValue={row.rating} disabled size="small" />
      ),
    },
    {
      label: () => t('mall.review.content'),
      prop: 'content',
      minWidth: '200px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <span class="truncate" title={row.content}>
          {row.content && row.content.length > 50 ? `${row.content.slice(0, 50)}...` : row.content}
        </span>
      ),
    },
    {
      label: () => t('mall.common.status'),
      prop: 'status',
      width: '90px',
      cellRender: ({ row }: { row: ReviewVo }) => (
        <ElTag type={statusTypeMap[row.status!] as any} size="small">
          {statusTextMap[row.status!] || row.status}
        </ElTag>
      ),
    },
    {
      label: () => t('mall.review.createdAt'),
      prop: 'created_at',
      width: '170px',
    },
    {
      label: () => t('mall.review.operationLabel'),
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
                  msg.confirm(t('mall.review.confirmApprove')).then(async () => {
                    const res = await reviewApprove(row.id!)
                    if (res.code === ResultCode.SUCCESS) {
                      msg.success(t('mall.review.approved'))
                      tableRef.value?.refresh()
                    }
                  })
                }}
              >
                {t('mall.review.approve')}
              </ElButton>
              <ElButton
                type="danger"
                link
                size="small"
                onClick={() => {
                  msg.confirm(t('mall.review.confirmReject')).then(async () => {
                    const res = await reviewReject(row.id!)
                    if (res.code === ResultCode.SUCCESS) {
                      msg.success(t('mall.review.rejected'))
                      tableRef.value?.refresh()
                    }
                  })
                }}
              >
                {t('mall.review.reject')}
              </ElButton>
            </>
          )}
          <ElButton
            type="primary"
            link
            size="small"
            onClick={() => onReply(row)}
          >
            {row.admin_reply ? t('mall.review.viewReply') : t('mall.review.replyReview')}
          </ElButton>
        </div>
      ),
    },
  ]
}
