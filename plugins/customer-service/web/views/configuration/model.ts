import type { Category, ConfigurationEntry, ConfigurationSnapshot, Resource } from '../../api/customer-service'
export const resourceNames: Record<Resource, string> = { categories: '问题类目', keyword_rules: '关键词规则', skill_groups: '技能组', agent_skills: '坐席技能', ai_connections: 'AI 连接', ai_agents: 'AI Agent' }
export function categoryPath(items: Category[], id?: number): string {
  const names: string[] = []; const visited = new Set<number>()
  while (id && !visited.has(id)) { visited.add(id); const category = items.find(i => i.id === id); if (!category) break; names.unshift(category.name); id = category.parent_id }
  return names.join(' / ')
}
export function validateCategoryTree(items: Category[]): string | undefined {
  for (const category of items) {
    const seen = new Set<number>([category.id]); let parent = category.parent_id
    while (parent) { if (seen.has(parent)) return '类目不能设置自身或后代为父级'; seen.add(parent); const item = items.find(i => i.id === parent); if (!item) return '父级类目不存在'; parent = item.parent_id }
    if (seen.size > 3) return '问题类目最多三级，移动父级时也需要保留后代层级'
    if (!category.name.trim() || !/^[a-zA-Z0-9_-]{1,60}$/.test(category.code || '')) return '请填写类目名称与唯一英文编码'
  }
}
export function emptyEntry(resource: Resource, snapshot: ConfigurationSnapshot): ConfigurationEntry {
  const common = { id: Math.max(snapshot._next_ids?.[resource] || 1, Math.max(0, ...snapshot[resource].map(i => i.id)) + 1), name: '', enabled: true }
  const defaults: Record<Resource, Record<string, any>> = {
    categories: { parent_id: 0, code: '', sort: 0, priority: undefined, force_human: false },
    keyword_rules: { category_id: undefined, match_type: 'contains', include_terms: [], exclude_terms: [], priority: 0 },
    skill_groups: { fallback_group_id: undefined }, agent_skills: { agent_id: undefined, group_id: undefined, category_id: undefined, include_descendants: true },
    ai_connections: { endpoint: '', credential_env: '' },
    ai_agents: { connection_id: undefined, model: '', prompt: '', retrieval_threshold: 0.5, max_output_tokens: 1024, category_ids: [], tool_allowlist: [], fallback_group_id: undefined },
  }
  return { ...common, ...defaults[resource] }
}
export function replaceEntry(snapshot: ConfigurationSnapshot, resource: Resource, entry: ConfigurationEntry): ConfigurationSnapshot {
  const next = JSON.parse(JSON.stringify(snapshot)) as ConfigurationSnapshot
  const index = next[resource].findIndex(i => i.id === entry.id)
  if (index < 0) (next[resource] as ConfigurationEntry[]).push(entry)
  else (next[resource] as ConfigurationEntry[])[index] = entry
  next._next_ids = { ...next._next_ids, [resource]: Math.max(next._next_ids?.[resource] || 1, entry.id + 1) }
  if (resource === 'categories') { const error = validateCategoryTree(next.categories); if (error) throw new Error(error) }
  return next
}
