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
import type { SeckillActivityVo } from '~/mall/api/seckill'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElTag, ElSwitch } from 'element-plus'
import { activityRemove, activityToggleStatus } from '~/mall/api/seckill'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTextMap: Record<string, string> = {
  pending: '待开始',
  active: '进行中',
  ended: '已结束',
  cancelled: '已取消',
}

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
}

export default function getTableColumns(dialog: UseDialogExpose, router: any, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '活动标题', prop: 'title', minWidth: '200px' },
    { label: () => '活动描述', prop: 'description', minWidth: '200px', showOverflowTooltip: true },
    { label: () => '场次数', prop: 'sessions_count', width: '100px',
      cellRender: ({ row }: { row: SeckillActivityVo }) => (
        <el-button
          type="primary"
          link
          onClick={() => router.push({ path: '/mall/seckill/sessions', query: { activity_id: row.id, activity_title: row.title } })}
        >
          {row.sessions_count ?? 0} 个场次
        </el-button>
      ),
    },
    { label: () => '状态', prop: 'status', width: '100px',
      cellRender: ({ row }: { row: SeckillActivityVo }) => (
        <ElTag type={statusTypeMap[row.status || 'pending'] as any}>
          {statusTextMap[row.status || 'pending']}
        </ElTag>
      ),
    },
    { label: () => '启用状态', prop: 'is_enabled', width: '100px',
      cellRender: ({ row }: { row: SeckillActivityVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('seckill:activity:update')}
          onChange={async () => {
            const res = await activityToggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success('状态切换成功')
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => '创建时间', prop: 'created_at', width: '170px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '200px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'sessions',
            show: () => hasAuth('seckill:session:list'),
            icon: 'material-symbols:schedule',
            text: () => '场次',
            onClick: ({ row }: { row: SeckillActivityVo }) => {
              router.push({ path: '/mall/seckill/sessions', query: { activity_id: row.id, activity_title: row.title } })
            },
          },
          {
            name: 'edit',
            show: () => hasAuth('seckill:activity:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: SeckillActivityVo }) => {
              dialog.setTitle('编辑活动')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('seckill:activity:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: SeckillActivityVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该活动吗？删除前请确保活动下没有场次。').then(async () => {
                const response = await activityRemove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
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
