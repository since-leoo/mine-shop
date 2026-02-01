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
import { couponRemove, couponToggleStatus } from '~/mall/api/coupon'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

export default function getTableColumns(
  formDialog: UseDialogExpose,
  router: any,
  tableRef: { value: MaProTableExpose | undefined },
  handleIssue: (row: CouponVo) => void,
): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { type: 'selection', width: '50px' },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '优惠券名称', prop: 'name', minWidth: '160px' },
    { label: () => '类型', prop: 'type', width: '90px',
      cellRender: ({ row }: { row: CouponVo }) => (
        <ElTag type={row.type === 'fixed' ? 'success' : 'warning'}>
          {row.type === 'fixed' ? '满减' : '折扣'}
        </ElTag>
      ),
    },
    { label: () => '优惠值', prop: 'value', width: '100px',
      cellRender: ({ row }: { row: CouponVo }) => row.type === 'percent'
        ? `${row.value ?? 0}%`
        : `¥${row.value ?? 0}`,
    },
    { label: () => '最低消费', prop: 'min_amount', width: '110px',
      cellRender: ({ row }: { row: CouponVo }) => `¥${row.min_amount ?? 0}`,
    },
    { label: () => '发放/总量', prop: 'total_quantity', width: '130px',
      cellRender: ({ row }: { row: CouponVo }) => `${row.issued_quantity ?? 0} / ${row.total_quantity ?? 0}`,
    },
    { label: () => '已使用', prop: 'used_quantity', width: '90px' },
    { label: () => '状态', prop: 'status', width: '120px',
      cellRender: ({ row }: { row: CouponVo }) => (
        <ElSwitch
          modelValue={row.status === 'active'}
          disabled={!hasAuth('coupon:update')}
          activeText="启用"
          inactiveText="停用"
          onChange={async () => {
            const res = await couponToggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success('状态切换成功')
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => '有效期', prop: 'start_time', minWidth: '220px',
      cellRender: ({ row }: { row: CouponVo }) => `${row.start_time ?? '--'} 至 ${row.end_time ?? '--'}`,
    },
    { label: () => '创建时间', prop: 'created_at', width: '170px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '260px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'issue',
            show: () => hasAuth('coupon:issue'),
            icon: 'ph:gift',
            text: () => '发放',
            onClick: ({ row }: { row: CouponVo }) => handleIssue(row),
          },
          {
            name: 'records',
            show: () => hasAuth('coupon:user:list'),
            icon: 'ph:users-three',
            text: () => '领取记录',
            onClick: ({ row }: { row: CouponVo }) => {
              router.push({ path: '/mall/coupon/users', query: { coupon_id: row.id } })
            },
          },
          {
            name: 'edit',
            show: () => hasAuth('coupon:update'),
            icon: 'material-symbols:edit-outline',
            text: () => '编辑',
            onClick: ({ row }: { row: CouponVo }) => {
              formDialog.setTitle('编辑优惠券')
              formDialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('coupon:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: CouponVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该优惠券吗？').then(async () => {
                const res = await couponRemove(row.id as number)
                if (res.code === ResultCode.SUCCESS) {
                  msg.success('删除成功')
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
