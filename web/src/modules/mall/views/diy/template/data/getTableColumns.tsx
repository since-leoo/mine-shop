import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { DiyTemplateVo } from '~/mall/api/diyTemplate'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElButton, ElDropdown, ElDropdownItem, ElDropdownMenu, ElTag } from 'element-plus'
import { disableDiyTemplate, enableDiyTemplate } from '~/mall/api/diyTemplate'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const pageTypeText: Record<string, string> = {
  miniprogram: '小程序',
  h5: 'H5',
  all: '通用',
}

export default function getTableColumns(
  dialog: UseDialogExpose,
  openApply: (row: DiyTemplateVo) => void,
): MaProTableColumns[] {
  const msg = useMessage()

  async function toggle(row: DiyTemplateVo, proxy: MaProTableExpose) {
    const res = row.is_enabled
      ? await disableDiyTemplate(row.id as number)
      : await enableDiyTemplate(row.id as number)
    if (res.code === ResultCode.SUCCESS) {
      msg.success(row.is_enabled ? '模板已禁用' : '模板已启用')
      await proxy.refresh()
    }
  }

  return [
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '模板名称', prop: 'name', minWidth: '180px' },
    {
      label: () => '分类',
      prop: 'category_id',
      width: '130px',
      cellRender: (data) => {
        const row = data.row as DiyTemplateVo
        return String(row.category?.name || row.category_id)
      },
    },
    { label: () => '页面键', prop: 'page_key', width: '140px' },
    {
      label: () => '页面类型',
      prop: 'page_type',
      width: '110px',
      cellRender: (data) => {
        const row = data.row as DiyTemplateVo
        return pageTypeText[row.page_type] || row.page_type
      },
    },
    {
      label: () => '启用',
      prop: 'is_enabled',
      width: '90px',
      cellRender: (data) => {
        const row = data.row as DiyTemplateVo
        return <ElTag type={row.is_enabled ? 'success' : 'info'}>{row.is_enabled ? '启用' : '禁用'}</ElTag>
      },
    },
    { label: () => '排序', prop: 'sort', width: '90px' },
    { label: () => '更新时间', prop: 'updated_at', minWidth: '170px' },
    {
      label: () => '操作',
      width: '210px',
      fixed: 'right',
      showOverflowTooltip: false,
      cellRender: (data) => {
        const row = data.row as DiyTemplateVo
        const proxy = data.attrs as MaProTableExpose
        return (
          <div class="diy-table-actions">
            {hasAuth('mall:diy:template:update') && (
              <ElButton
                type="primary"
                size="small"
                onClick={() => openApply(row)}
              >
                <ma-svg-icon name="ph:download-simple" size="14" />
                套用
              </ElButton>
            )}
            {hasAuth('mall:diy:template:update') && (
              <ElButton size="small" onClick={() => {
                dialog.setTitle('编辑模板')
                dialog.open({ formType: 'edit', data: row })
              }}
              >
                <ma-svg-icon name="material-symbols:edit-outline" size="14" />
                编辑
              </ElButton>
            )}
            {hasAuth('mall:diy:template:update') && (
              <ElDropdown trigger="click" onCommand={() => toggle(row, proxy)}>
                {{
                  default: () => (
                    <ElButton size="small">
                      更多
                      <ma-svg-icon name="ph:caret-down" size="13" />
                    </ElButton>
                  ),
                  dropdown: () => (
                    <ElDropdownMenu>
                      <ElDropdownItem command="toggle">{row.is_enabled ? '禁用' : '启用'}</ElDropdownItem>
                    </ElDropdownMenu>
                  ),
                }}
              </ElDropdown>
            )}
          </div>
        )
      },
    },
  ]
}
