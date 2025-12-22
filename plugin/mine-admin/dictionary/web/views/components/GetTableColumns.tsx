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
import type { DictionaryVo } from '../../api/Dictionary.ts'
import type { DictionaryTypeVo } from '../../api/DictionaryType.ts'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { useMessage } from '@/hooks/useMessage.ts'
import { deleteByIds } from '../../api/Dictionary.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { ElTag } from 'element-plus'

export default function getTableColumns(dialog: UseDialogExpose, formRef: any, t: any, typeInfo: DictionaryTypeVo): MaProTableColumns[] {
  const dictStore = useDictStore()
  const msg = useMessage()

  const showBtn = (auth: string | string[], row: DictionaryVo) => {
    return hasAuth(auth)
  }

  return [
    // 多选列
    { type: 'selection', showOverflowTooltip: false, label: () => t('crud.selection') },
    // 普通列
    { label: () => '字典标签', prop: 'label' },
    { label: () => '字典编码', prop: 'code' },
    { label: () => '字典值', prop: 'value' },
    { label: () => '国际化', prop: 'i18n', cellRender: ({ row }) => row.i18n ?? '-' },
    { label: () => '国际化', prop: 'i18n_scope',
      cellRender: ({ row }) => (
        <ElTag type={dictStore.t('i18n-scope', row.i18n_scope, 'color')}>
          {t(dictStore.t('i18n-scope', row.i18n_scope, 'i18n'))}
        </ElTag>
      ),
    },
    { label: () => '状态', prop: 'status',
      cellRender: ({ row }) => (
        <ElTag type={dictStore.t('system-status', row.status, 'color')}>
          {t(dictStore.t('system-status', row.status, 'i18n'))}
        </ElTag>
      ),
    },
    { label: () => '文字颜色', prop: 'color', cellRender: ({ row }) => row.i18n ?? '-' },
    { label: () => '排序', prop: 'sort' },
    // 操作列
    {
      type: 'operation',
      label: () => t('crud.operation'),
      operationConfigure: {
        actions: [
          {
            name: 'edit',
            icon: 'i-heroicons:pencil',
            show: ({ row }) => showBtn('dataCenter:dictionary:update', row),
            text: () => t('crud.edit'),
            onClick: ({ row }) => {
              dialog.setTitle(t('crud.edit'))
              dialog.open({ formType: 'edit', typeInfo: typeInfo.value, data: row })
            },
          },
          {
            name: 'del',
            show: ({ row }) => showBtn('dataCenter:dictionary:delete', row),
            icon: 'i-heroicons:trash',
            text: () => t('crud.delete'),
            onClick: ({ row }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('crud.delDataMessage')).then(async () => {
                const response = await deleteByIds([row.id])
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('crud.delSuccess'))
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
