async function request(method: string, url: string, data?: unknown, config?: { params?: Record<string, string> }) {
  const query = new URLSearchParams(config?.params).toString()
  const response = await fetch(url + (query ? `?${query}` : ''), {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: data === undefined ? undefined : JSON.stringify(data),
  })
  const result = await response.json()
  if (!response.ok) {
    throw Object.assign(new Error(`Request failed with status code ${response.status}`), {
      response: { status: response.status, data: result },
    })
  }
  if (result.code !== 200) {
    throw result
  }
  return result
}

export default () => ({
  get: (url: string, config?: { params?: Record<string, string> }) => request('GET', url, undefined, config),
  post: (url: string, data?: unknown) => request('POST', url, data),
  put: (url: string, data?: unknown) => request('PUT', url, data),
})
