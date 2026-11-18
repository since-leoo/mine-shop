async function request(method: string, url: string, data?: any, config?: any) {
  const query = new URLSearchParams(config?.params || {}).toString()
  const response = await fetch(url + (query ? `?${query}` : ''), { method, headers: { 'Content-Type': 'application/json' }, body: data === undefined ? undefined : JSON.stringify(data) })
  const result = await response.json()
  if (!response.ok) throw { response: { data: result } }
  return result
}
export default () => ({ get: (url: string, config?: any) => request('GET', url, undefined, config), post: (url: string, data?: any) => request('POST', url, data), put: (url: string, data?: any) => request('PUT', url, data), delete: (url: string) => request('DELETE', url) })
