import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { ExportTaskVo } from '../../../api/export'
import { ElTag, ElProgress, ElTooltip } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { remove, download } from '../../../api/export'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  processing: 'warning',
  success: 'success',
  failed: 'danger',
  expired: 'info',
}

function formatFileSize(bytes?: number): string {
  if (!bytes || bytes === 0) return '--'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export default function getTableColumns(
  tableRef: { value: MaProTableExpose | undefined },
): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    pending: t('export.statusPending'),
    processing: t('export.statusProcessing'),
    success: t('export.statusSuccess'),
    failed: t('export.statusFailed'),
    expired: t('export.statusExpired'),
  }

  return [
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => t('export.taskName'), prop: 'task_name', minWidth: '160px' },
    {
      label: () => t('export.exportFormat'),
      prop: 'export_format',
      width: '90px',
      cellRender: (({ row }: any) => (
        <span>{(row.export_format ?? '').toUpperCase()}</span>
      )) as any,
    },
    {
      label: () => t('export.status'),
      prop: 'status',
      width: '100px',
      cellRender: (({ row }: any) => (
        <ElTag type={statusTypeMap[row.status] as any} size="small">
          {statusTextMap[row.status] || row.status}
        </ElTag>
      )) as any,
    },
    {
      label: () => t('export.progress'),
      prop: 'progress',
      width: '140px',
      cellRender: (({ row }: any) => (
        row.status === 'processing'
          ? <ElProgress percentage={row.progress ?? 0} />
          : <span>{row.status === 'success' ? '100%' : '--'}</span>
      )) as any,
    },
    {
      label: () => t('export.fileSize'),
      prop: 'file_size',
      width: '100px',
      cellRender: (({ row }: any) => (
        <span>{formatFileSize(row.file_size)}</span>
      )) as any,
    },
    {
      label: () => t('export.retryCount'),
      prop: 'retry_count',
      width: '100px',
      cellRender: (({ row }: any) => (
        <span>{row.retry_count ?? 0}</span>
      )) as any,
    },
    {
      label: () => t('export.errorMessage'),
      prop: 'error_message',
      minWidth: '200px',
      cellRender: (({ row }: any) => (
        row.error_message
          ? <ElTooltip content={row.error_message} placement="top" effect="dark" popperStyle="max-width: 400px; word-break: break-all;">
              <ElTag type="danger" size="small" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer;">
                {row.error_message}
              </ElTag>
            </ElTooltip>
          : <span>--</span>
      )) as any,
    },
    { label: () => t('export.createdAt'), prop: 'created_at', width: '170px' },
    {
      type: 'operation',
      label: () => t('crud.operation'),
      width: '150px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'download',
            show: ({ row }: any) => row.status === 'success' && hasAuth('export:task:download'),
            icon: 'mdi:download',
            text: () => t('export.download'),
            onClick: async ({ row }: any) => {
              const res = await download(row.id as number)
              if (res.data?.url) {
                window.open(res.data.url, '_blank')
              }
            },
          },
          {
            name: 'del',
            show: () => hasAuth('export:task:delete'),
            icon: 'mdi:delete',
            text: () => t('export.delete'),
            onClick: async ({ row }: any, proxy: MaProTableExpose) => {
              msg.delConfirm(t('export.confirmDelete')).then(async () => {
                const res = await remove(row.id as number)
                if (res.code === ResultCode.SUCCESS) {
                  msg.success(t('export.deleteSuccess'))
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
