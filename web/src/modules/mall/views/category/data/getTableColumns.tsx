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
import type { CategoryVo } from '~/mall/api/category'
import type { Ref } from 'vue'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElTag } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { remove } from '~/mall/api/category'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTypeMap: Record<string, string> = {
  active: 'success',
  inactive: 'info',
}

export default function getTableColumns(
  dialog: UseDialogExpose,
  formRef: any,
  parentNameMap: Ref<Record<number, string>>,
): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { type: 'index', width: '60px' },
    { label: () => t('mall.category.categoryName'), prop: 'name', minWidth: '180px' },
    { label: () => t('mall.category.parentCategory'), prop: 'parent_id', minWidth: '160px',
      cellRender: ({ row }: { row: CategoryVo }) => {
        if (!row.parent_id) {
          return t('mall.category.topLevel')
        }
        return parentNameMap.value[row.parent_id] || `#${row.parent_id}`
      },
    },
    { label: () => t('mall.category.statusLabel'), prop: 'status', width: '100px',
      cellRender: ({ row }: { row: CategoryVo }) => (
        <ElTag type={statusTypeMap[row.status || 'inactive'] as any}>
          {row.status === 'active' ? t('mall.common.enabled') : t('mall.common.disabled')}
        </ElTag>
      ),
    },
    { label: () => t('mall.category.sortLabel'), prop: 'sort', width: '90px' },
    { label: () => t('mall.category.updateTime'), prop: 'updated_at', minWidth: '160px' },
    {
      type: 'operation',
      label: () => t('mall.category.operation'),
      width: '200px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('product:category:update'),
            icon: 'material-symbols:edit',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: CategoryVo }) => {
              dialog.setTitle(t('mall.category.editCategory'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('product:category:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: CategoryVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.category.confirmDeleteSingle')).then(async () => {
                const response = await remove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.category.deleteSuccess'))
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
