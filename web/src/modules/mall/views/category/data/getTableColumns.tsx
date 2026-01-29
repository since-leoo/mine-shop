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

  return [
    { type: 'selection', showOverflowTooltip: false },
    { type: 'index', width: '60px' },
    { label: () => '分类名称', prop: 'name', minWidth: '180px' },
    { label: () => '上级分类', prop: 'parent_id', minWidth: '160px',
      cellRender: ({ row }: { row: CategoryVo }) => {
        if (!row.parent_id) {
          return '顶级分类'
        }
        return parentNameMap.value[row.parent_id] || `#${row.parent_id}`
      },
    },
    { label: () => '状态', prop: 'status', width: '100px',
      cellRender: ({ row }: { row: CategoryVo }) => (
        <ElTag type={statusTypeMap[row.status || 'inactive'] as any}>
          {row.status === 'active' ? '启用' : '停用'}
        </ElTag>
      ),
    },
    { label: () => '排序', prop: 'sort', width: '90px' },
    { label: () => '更新时间', prop: 'updated_at', minWidth: '160px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '200px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('product:category:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: CategoryVo }) => {
              dialog.setTitle('编辑分类')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('product:category:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: CategoryVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该分类吗？').then(async () => {
                const response = await remove(row.id as number)
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
