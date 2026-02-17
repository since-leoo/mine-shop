/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { CouponVo } from '~/mall/api/coupon'

import { ElTag, ElSwitch } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { couponRemove, couponToggleStatus } from '~/mall/api/coupon'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { formatYuan } from '@/utils/price'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

export default function getTableColumns(
  formDialog: UseDialogExpose,
  router: any,
  tableRef: { value: MaProTableExpose | undefined },
  handleIssue: (row: CouponVo) => void,
): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  return [
    { type: 'selection', width: '50px' },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => t('mall.coupon.name'), prop: 'name', minWidth: '160px' },
    { label: () => t('mall.coupon.typeLabel'), prop: 'type', width: '90px',
      cellRender: ({ row }: { row: CouponVo }) => (
        <ElTag type={row.type === 'fixed' ? 'success' : 'warning'}>
          {row.type === 'fixed' ? t('mall.coupon.typeFixed') : t('mall.coupon.typePercent')}
        </ElTag>
      ),
    },
    { label: () => t('mall.coupon.valueLabel'), prop: 'value', width: '100px',
      cellRender: ({ row }: { row: CouponVo }) => row.type === 'percent'
        ? `${(row.value ?? 0) / 100}x`
        : `¥${formatYuan(row.value)}`,
    },
    { label: () => t('mall.coupon.minAmountLabel'), prop: 'min_amount', width: '110px',
      cellRender: ({ row }: { row: CouponVo }) => `¥${formatYuan(row.min_amount)}`,
    },
    { label: () => t('mall.coupon.totalCountLabel'), prop: 'total_quantity', width: '130px',
      cellRender: ({ row }: { row: CouponVo }) => `${row.issued_quantity ?? 0} / ${row.total_quantity ?? 0}`,
    },
    { label: () => t('mall.couponUser.used'), prop: 'used_quantity', width: '90px' },
    { label: () => t('mall.coupon.statusLabel'), prop: 'status', width: '120px',
      cellRender: ({ row }: { row: CouponVo }) => (
        <ElSwitch
          modelValue={row.status === 'active'}
          disabled={!hasAuth('coupon:update')}
          onChange={async () => {
            const res = await couponToggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success(t('mall.operationSuccess'))
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => t('mall.coupon.validityLabel'), prop: 'start_time', minWidth: '220px',
      cellRender: ({ row }: { row: CouponVo }) => `${row.start_time ?? '--'} ~ ${row.end_time ?? '--'}`,
    },
    { label: () => t('crud.createTime'), prop: 'created_at', width: '170px' },
    {
      type: 'operation',
      label: () => t('crud.operation'),
      width: '260px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'issue',
            show: () => hasAuth('coupon:issue'),
            icon: 'ph:gift',
            text: () => t('mall.coupon.issueAction'),
            onClick: ({ row }: { row: CouponVo }) => handleIssue(row),
          },
          {
            name: 'records',
            show: () => hasAuth('coupon:user:list'),
            icon: 'ph:users-three',
            text: () => t('mall.couponUser.recordTitle'),
            onClick: ({ row }: { row: CouponVo }) => {
              router.push({ path: '/mall/coupon/users', query: { coupon_id: row.id } })
            },
          },
          {
            name: 'edit',
            show: () => hasAuth('coupon:update'),
            icon: 'material-symbols:edit-outline',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: CouponVo }) => {
              formDialog.setTitle(t('mall.coupon.editCoupon'))
              formDialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('coupon:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: CouponVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.coupon.confirmDelete')).then(async () => {
                const res = await couponRemove(row.id as number)
                if (res.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.common.deleteSuccess'))
                  await proxy.refresh()
                }
              })
            },
          },
        ],
      },
    },
  ]
}
