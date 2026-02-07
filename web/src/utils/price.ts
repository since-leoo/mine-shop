/**
 * 金额工具函数
 * 后端存储单位为分（int），前端展示需要转换为元
 */

/** 分 → 元字符串，如 9990 → '99.90' */
export function formatYuan(cents: number | string | null | undefined): string {
  const val = Number(cents ?? 0)
  if (Number.isNaN(val)) return '0.00'
  return (val / 100).toFixed(2)
}

/** 分 → 带¥前缀，如 9990 → '¥99.90' */
export function formatYuanWithSymbol(cents: number | string | null | undefined): string {
  return `¥${formatYuan(cents)}`
}

/** 元 → 分（四舍五入取整） */
export function yuanToCents(yuan: number | string | null | undefined): number {
  const val = Number(yuan ?? 0)
  if (Number.isNaN(val)) return 0
  return Math.round(val * 100)
}

/** 分 → 元（数值） */
export function centsToYuan(cents: number | string | null | undefined): number {
  const val = Number(cents ?? 0)
  if (Number.isNaN(val)) return 0
  return val / 100
}
