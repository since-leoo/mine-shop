import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { DiyPageVo } from '~/mall/api/diyPage'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElButton, ElDropdown, ElDropdownItem, ElDropdownMenu, ElTag } from 'element-plus'
import { copyDiyPage, disableDiyPage, enableDiyPage } from '~/mall/api/diyPage'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const pageTypeText: Record<string, string> = {
  miniprogram: '小程序',
  h5: 'H5',
  all: '通用',
}

const statusType: Record<string, string> = {
  draft: 'warning',
  published: 'success',
  disabled: 'info',
}

export default function getTableColumns(
  dialog: UseDialogExpose,
  router: any,
  openRecords: (row: DiyPageVo) => void,
  openSaveAsTemplate: (row: DiyPageVo) => void,
  openPreview: (row: DiyPageVo) => void,
): MaProTableColumns[] {
  const msg = useMessage()

  async function copy(row: DiyPageVo, proxy: MaProTableExpose) {
    const res = await copyDiyPage(row.id as number)
    if (res.code === ResultCode.SUCCESS) {
      msg.success('页面已复制')
      await proxy.refresh()
    }
  }

  async function toggle(row: DiyPageVo, proxy: MaProTableExpose) {
    if (!row.is_enabled && !row.published_version_id) {
      msg.warning('请先发布页面后再启用')
      return
    }
    const res = row.is_enabled
      ? await disableDiyPage(row.id as number)
      : await enableDiyPage(row.id as number)
    if (res.code === ResultCode.SUCCESS) {
      msg.success(row.is_enabled ? '页面已禁用' : '页面已启用')
      await proxy.refresh()
    }
  }

  function handleCommand(command: string, row: DiyPageVo, proxy: MaProTableExpose) {
    const actions: Record<string, () => void | Promise<void>> = {
      copy: () => copy(row, proxy),
      records: () => openRecords(row),
      preview: () => openPreview(row),
      template: () => openSaveAsTemplate(row),
      toggle: () => toggle(row, proxy),
    }
    return actions[command]?.()
  }

  return [
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '页面名称', prop: 'title', minWidth: '180px' },
    { label: () => '页面键', prop: 'page_key', width: '130px' },
    {
      label: () => '页面类型',
      prop: 'page_type',
      width: '110px',
      cellRender: (data) => {
        const row = data.row as DiyPageVo
        return pageTypeText[row.page_type] || row.page_type
      },
    },
    {
      label: () => '启用',
      prop: 'is_enabled',
      width: '90px',
      cellRender: (data) => {
        const row = data.row as DiyPageVo
        return <ElTag type={row.is_enabled ? 'success' : 'info'}>{row.is_enabled ? '启用' : '禁用'}</ElTag>
      },
    },
    {
      label: () => '发布',
      prop: 'status',
      width: '100px',
      cellRender: (data) => {
        const row = data.row as DiyPageVo
        return (
          <ElTag type={(statusType[row.status || 'draft'] || 'warning') as any}>
            {row.published_version_id ? '已发布' : '未发布'}
          </ElTag>
        )
      },
    },
    { label: () => '更新时间', prop: 'updated_at', minWidth: '170px' },
    {
      label: () => '操作',
      width: '230px',
      fixed: 'right',
      showOverflowTooltip: false,
      cellRender: (data) => {
        const row = data.row as DiyPageVo
        const proxy = data.attrs as MaProTableExpose
        return (
          <div class="diy-table-actions">
            {hasAuth('mall:diy:page:update') && (
              <ElButton
                type="primary"
                size="small"
                onClick={() => router.push({ path: '/mall/diy/editor', query: { id: row.id } })}
              >
                <ma-svg-icon name="ph:paint-brush" size="14" />
                装修
              </ElButton>
            )}
            {hasAuth('mall:diy:page:update') && (
              <ElButton
                size="small"
                onClick={() => {
                  dialog.setTitle('编辑页面')
                  dialog.open({ formType: 'edit', data: row })
                }}
              >
                <ma-svg-icon name="material-symbols:edit-outline" size="14" />
                编辑
              </ElButton>
            )}
            <ElDropdown trigger="click" onCommand={(command: string) => handleCommand(command, row, proxy)}>
              {{
                default: () => (
                  <ElButton size="small">
                    更多
                    <ma-svg-icon name="ph:caret-down" size="13" />
                  </ElButton>
                ),
                dropdown: () => (
                  <ElDropdownMenu>
                    {hasAuth('mall:diy:page:create') && <ElDropdownItem command="copy">复制</ElDropdownItem>}
                    {hasAuth('mall:diy:page:publish') && <ElDropdownItem command="records">发布记录</ElDropdownItem>}
                    {hasAuth('mall:diy:page:read') && <ElDropdownItem command="preview">预览</ElDropdownItem>}
                    {hasAuth('mall:diy:template:create') && <ElDropdownItem command="template">存为模板</ElDropdownItem>}
                    {hasAuth('mall:diy:page:enable') && <ElDropdownItem command="toggle">{row.is_enabled ? '禁用' : '启用'}</ElDropdownItem>}
                  </ElDropdownMenu>
                ),
              }}
            </ElDropdown>
          </div>
        )
      },
    },
  ]
}
