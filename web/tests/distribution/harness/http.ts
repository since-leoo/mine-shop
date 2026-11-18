async function request(method: string, url: string, data?: unknown, config?: { params?: Record<string, unknown> }) {
  const query = new URLSearchParams(Object.entries(config?.params ?? {}).filter(([, value]) => value !== undefined).map(([key, value]) => [key, String(value)]))
  const response = await fetch(url + (query.size ? `?${query}` : ''), { method, headers: { 'Content-Type': 'application/json' }, body: data === undefined ? undefined : JSON.stringify(data) })
  const result = await response.json()
  if (!response.ok) {
    throw Object.assign(new Error(result.message || '请求失败'), { response: { data: result } })
  }
  return result
}
export default () => ({ get: (url: string, config?: any) => request('GET', url, undefined, config), put: (url: string, data?: unknown) => request('PUT', url, data), post: (url: string, data?: unknown) => request('POST', url, data) })
