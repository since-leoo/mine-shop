// Transport only: all endpoint responses and failures are controlled by Playwright.
async function request(method: string, url: string, data?: unknown, config?: { params?: Record<string, any> }) {
  const query = new URLSearchParams(config?.params || {}).toString()
  const response = await fetch(url + (query ? `?${query}` : ''), {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: data === undefined ? undefined : JSON.stringify(data),
  })
  const result = await response.json()
  if (!response.ok) throw { response: { data: result } }
  return result
}
export default () => ({
  get: (url: string, config?: any) => request('GET', url, undefined, config),
  post: (url: string, data?: unknown) => request('POST', url, data),
  put: (url: string, data?: unknown) => request('PUT', url, data),
  delete: (url: string, data?: unknown) => request('DELETE', url, data),
})
