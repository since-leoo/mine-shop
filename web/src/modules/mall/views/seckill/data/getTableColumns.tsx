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
import { useI18n } from 'vue-i18n'
import { activityRemove, activityToggleStatus } from '~/mall/api/seckill'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
}

export default function getTableColumns(dialog: UseDialogExpose, router: any, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    pending: t('mall.activityStatus.pending'),
    active: t('mall.activityStatus.active'),
    ended: t('mall.activityStatus.ended'),
    cancelled: t('mall.activityStatus.cancelled'),
  }

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => t('mall.seckill.activityTitle'), prop: 'title', minWidth: '200px' },
    { label: () => t('mall.seckill.activityDesc'), prop: 'description', minWidth: '200px', showOverflowTooltip: true },
    { label: () => t('mall.seckill.sessionsCount'), prop: 'sessions_count', width: '100px',
      cellRender: ({ row }: { row: SeckillActivityVo }) => (
        <el-button
          type="primary"
          link
          onClick={() => router.push({ path: '/mall/seckill/sessions', query: { activity_id: row.id, activity_title: row.title } })}
        >
          {row.sessions_count ?? 0} {t('mall.seckill.sessionsUnit')}
        </el-button>
      ),
    },
    { label: () => t('mall.common.status'), prop: 'status', width: '100px',
      cellRender: ({ row }: { row: SeckillActivityVo }) => (
        <ElTag type={statusTypeMap[row.status || 'pending'] as any}>
          {statusTextMap[row.status || 'pending']}
        </ElTag>
      ),
    },
    { label: () => t('mall.seckill.enabledStatus'), prop: 'is_enabled', width: '100px',
      cellRender: ({ row }: { row: SeckillActivityVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('seckill:activity:update')}
          onChange={async () => {
            const res = await activityToggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success(t('mall.seckill.statusToggleSuccess'))
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => t('mall.seckill.createdAt'), prop: 'created_at', width: '170px' },
    {
      type: 'operation',
      label: () => t('mall.seckill.operation'),
      width: '200px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'sessions',
            show: () => hasAuth('seckill:session:list'),
            icon: 'material-symbols:schedule',
            text: () => t('mall.seckill.sessions'),
            onClick: ({ row }: { row: SeckillActivityVo }) => {
              router.push({ path: '/mall/seckill/sessions', query: { activity_id: row.id, activity_title: row.title } })
            },
          },
          {
            name: 'edit',
            show: () => hasAuth('seckill:activity:update'),
            icon: 'material-symbols:edit',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: SeckillActivityVo }) => {
              dialog.setTitle(t('mall.seckill.editActivity'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('seckill:activity:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: SeckillActivityVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.seckill.confirmDeleteSingle')).then(async () => {
                const response = await activityRemove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.seckill.deleteSuccess'))
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
