function specValueText(value: unknown): string {
  if (typeof value === 'string') return value.trim();
  if (typeof value === 'number' && Number.isFinite(value)) return String(value);
  return '';
}

/** 结算快照可能包含具名规格、历史字符串数组或按规格名索引的对象。 */
export function formatOrderSpecValues(specValues: unknown): string {
  const values = Array.isArray(specValues)
    ? specValues
    : specValues && typeof specValues === 'object'
      ? Object.values(specValues)
      : [specValues];

  return values.map((value: unknown) => {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      const spec = value as Record<string, unknown>;
      return specValueText(spec.value ?? spec.valueName ?? spec.specValue);
    }
    return specValueText(value);
  }).filter(Boolean).join(' ');
}
