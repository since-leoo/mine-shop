export async function request(options: { url: string, method?: string, data?: any }) {
  const method = options.method || 'GET'
  const query = method === 'GET' && options.data ? `?${new URLSearchParams(options.data)}` : ''
  const response = await fetch(options.url + query, { method, headers: { 'Content-Type': 'application/json' }, body: method === 'GET' ? undefined : JSON.stringify(options.data) })
  const body = await response.json(); if (!response.ok) throw new Error(body.message || '请求失败'); return body.data
}
