import type { Task } from '../api/supply-chain'

export function errorMessage(error: unknown): string {
  const value = error as { response?: { data?: { message?: string } }, message?: string }
  return value?.response?.data?.message || value?.message || '操作未完成，请稍后重试'
}

export function money(value?: number): string {
  return typeof value === 'number' && Number.isFinite(value) ? `¥${(value / 100).toFixed(2)}` : '—'
}

export const statusLabels: Record<string, string> = {
  active: '运行中', pause_new: '暂停新业务', draining: '处理存量',
  pending: '待执行', queued: '已入队', running: '执行中', processing: '处理中',
  succeeded: '成功', success: '成功', completed: '已完成',
  failed: '失败', retry_wait: '等待重试', retrying: '等待重试',
  unknown: '结果待确认', cancelled: '已取消', skipped: '已跳过',
  imported: '已导入', disabled: '已停用', submitted: '已提交',
  shipped: '已发货', partial_shipped: '部分发货', manual: '待人工处理',
  pending_review: '待审核采购售后',
  submitting: '提交中', accepted: '上游已受理', started: '请求已开始', available: '可供货', unavailable: '暂停供货',
}

export function statusLabel(value: string): string {
  return statusLabels[value] || value || '—'
}

export function statusType(value: string): 'success' | 'warning' | 'danger' | 'info' {
  if (['succeeded', 'success', 'completed', 'active', 'imported', 'shipped'].includes(value)) return 'success'
  if (['failed', 'manual'].includes(value)) return 'danger'
  if (['unknown', 'retry_wait', 'retrying', 'pause_new', 'draining'].includes(value)) return 'warning'
  return 'info'
}

export function canRetry(task: Task): boolean {
  return task.retryable === true && ['failed', 'retry_wait', 'manual'].includes(task.status) && task.next_action !== 'query'
}

// 后端必须先脱敏。前端再遮蔽结构化敏感字段，且始终以文本而非 HTML 展示。
export function safeJson(value: unknown): string {
  if (value === undefined || value === null) return '—'
  return JSON.stringify(value, (key, entry) => {
    const normalized = key.replace(/[-_]/g, '').toLowerCase()
    if (/(secret|token|authorization|password|apisign|membersign|signature)/.test(normalized)) return '[已隐藏]'
    return entry
  }, 2)
}
