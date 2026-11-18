import type { ProductSpecValue } from '~/mall/api/product'

type SpecName = (index: number) => string
const defaultSpecName: SpecName = index => `规格${index + 1}`

function scalarText(value: unknown): string {
  if (typeof value === 'string') return value
  if (typeof value === 'number' && Number.isFinite(value)) return String(value)
  if (typeof value === 'boolean') return String(value)
  return ''
}

/** 在编辑边界兼容历史规格，内部和提交时保留名称，避免对象被隐式转成字符串。 */
export function normalizeSpecValues(input: unknown, nameAt: SpecName = defaultSpecName): ProductSpecValue[] {
  const entries: [string | undefined, unknown][] = Array.isArray(input)
    ? input.map(value => [undefined, value])
    : input && typeof input === 'object'
      ? Object.entries(input)
      : typeof input === 'string'
        ? input.split(',').map(value => [undefined, value.trim()])
        : []

  return entries.flatMap(([key, raw], index) => {
    const named = raw !== null && typeof raw === 'object' && !Array.isArray(raw)
      ? raw as Record<string, unknown>
      : undefined
    const value = scalarText(named ? (named.value ?? named.spec_value) : raw)
    if (!value.trim()) return []
    const name = scalarText(named?.name ?? named?.title ?? key).trim() || nameAt(index)
    const spec: ProductSpecValue = { name, value }
    if (named?.image === null || typeof named?.image === 'string') spec.image = named.image
    return [spec]
  })
}

export function specValueTexts(input: unknown): string[] {
  return normalizeSpecValues(input).map(spec => spec.value)
}

export function specValuesKey(input: unknown): string {
  // JSON 保留维度边界，避免 “红/白, L” 与 “红, 白/L” 的组合相互覆盖。
  return JSON.stringify(specValueTexts(input))
}
