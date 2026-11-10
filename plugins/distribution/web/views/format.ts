export function money(value: unknown): string {
  if (value === undefined || value === null) {
    return '—'
  }
  const amount = Number(value)
  return Number.isFinite(amount) ? `¥${(amount / 100).toFixed(2)}` : '—'
}

export function errorMessage(error: unknown): string {
  const failure = error as { response?: { data?: { message?: string } }, message?: string }
  return failure?.response?.data?.message || failure?.message || '操作失败，请重试'
}

export const statusNames: Record<string, string> = {
  pending: '待处理', settled: '已结算', cancelled: '已取消', approved: '待打款',
  paid: '已支付', rejected: '已驳回', failed: '打款失败', completed: '已完成',
  shipped: '已发货', refunded: '已退款', closed: '已关闭',
}
