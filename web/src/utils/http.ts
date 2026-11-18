/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios'
import axios from 'axios'
import { ElNotification } from 'element-plus'
import { useDebounceFn } from '@vueuse/core'
import { useNProgress } from '@vueuse/integrations/useNProgress'
import useCache from '@/hooks/useCache.ts'
import { ResultCode } from './ResultCode.ts'

const { isLoading } = useNProgress()
const cache = useCache()
const requestList = ref<Array<{ resolve: () => void, reject: (error: unknown) => void }>>([])
const isRefreshToken = ref<boolean>(false)

function createHttp(baseUrl: string | null = null, config: AxiosRequestConfig = {}): AxiosInstance {
  const env = import.meta.env
  return axios.create({
    baseURL: baseUrl ?? (env.VITE_OPEN_PROXY === 'true' ? env.VITE_PROXY_PREFIX : env.VITE_APP_API_BASEURL),
    timeout: 1000 * 5,
    responseType: 'json',
    ...config,
  })
}

const http: AxiosInstance = createHttp()

http.interceptors.request.use(

  async (config) => {
    isLoading.value = true
    const userStore = useUserStore()
    /**
     * 全局拦截请求发送前提交的参数
     */
    if (userStore.isLogin && config.headers) {
      config.headers = Object.assign({
        'Authorization': `Bearer ${userStore.token}`,
        'Accept-Language': userStore.getLanguage(),
      }, config.headers)
    }

    await usePluginStore().callHooks('networkRequest', config)
    return config
  },
)

let isLogout = false

async function handleResponse(response: AxiosResponse): Promise<any> {
  isLoading.value = false
  const userStore = useUserStore()
  await usePluginStore().callHooks('networkResponse', response)
  const config = response.config as typeof response.config & { _tokenRefreshed?: boolean }

  if (config.responseType === 'blob' || config.responseType === 'arraybuffer') {
    // 处理 JSON 格式的错误响应
    const contentType = String(response.headers['content-type'] ?? response.data?.type ?? '')
    if (/\b(?:application|text)\/(?:[\w.-]+\+)?json\b/i.test(contentType)) {
      try {
        const text = response.data instanceof Blob
          ? await response.data.text()
          : new TextDecoder().decode(response.data)
        const result = JSON.parse(text)
        if (!result || typeof result !== 'object' || result.code === ResultCode.SUCCESS) {
          throw new Error('下载接口未返回文件')
        }
        response.data = { ...result, code: result.code ?? (response.status >= 400 ? response.status : ResultCode.FAIL) }
      }
      catch {
        const error = new Error('下载响应格式错误，请重试')
        ElNotification({ title: '错误', message: error.message, type: 'error' })
        throw error
      }
    }
    else {
      // 正常的文件下载响应
      const disposition = String(response.headers['content-disposition'] ?? '')
      const encodedName = disposition.match(/filename\*=UTF-8''([^;\n]*)/i)?.[1]
      let fileName = encodedName ?? disposition.match(/filename=(?:"([^"]*)"|([^;\n]*))/i)?.slice(1).find(Boolean) ?? '未命名文件'
      try {
        fileName = decodeURIComponent(fileName)
      }
      catch {
        // 非百分号编码的文件名保持原值。
      }
      return { data: response.data, fileName, headers: response.headers }
    }
  }

  if (response?.data?.code === ResultCode.SUCCESS) {
    return Promise.resolve(response.data)
  }
  else {
    switch (response?.data?.code) {
      case ResultCode.UNAUTHORIZED:
      {
        const logout = async () => {
          if (isLogout === false) {
            isLogout = true
            setTimeout(() => isLogout = false, 5000)
            ElNotification({
              title: '提示',
              message: response?.data?.message ?? '登录已过期',
              type: 'warning',
            })
            await useUserStore().logout()
          }
        }
        if (!userStore.isLogin || !cache.get('refresh_token') || config._tokenRefreshed) {
          await logout()
          break
        }
        // 检查token是否需要刷新
        if (!isRefreshToken.value) {
          isRefreshToken.value = true
          try {
            const refreshTokenResponse = await createHttp(null, {
              headers: {
                Authorization: `Bearer ${cache.get('refresh_token')}`,
              },
            }).post('/admin/passport/refresh')

            if (refreshTokenResponse.data.code !== 200) {
              throw refreshTokenResponse.data
            }
            else {
              const { data } = refreshTokenResponse.data
              userStore.token = data.access_token
              cache.set('token', data.access_token)
              cache.set('expire', useDayjs().unix() + data.expire_at, { exp: data.expire_at })
              cache.set('refresh_token', data.refresh_token)

              config.headers!.Authorization = `Bearer ${userStore.token}`
              config._tokenRefreshed = true
              requestList.value.forEach(request => request.resolve())
              requestList.value = []
              return http(config)
            }
          }
          catch (error: unknown) {
            requestList.value.forEach(request => request.reject(error))
            await logout()
            break
          }
          finally {
            requestList.value = []
            isRefreshToken.value = false
          }
        }
        else {
          return new Promise((resolve, reject) => {
            requestList.value.push({
              resolve: () => {
                config.headers!.Authorization = `Bearer ${cache.get('token')}`
                config._tokenRefreshed = true
                resolve(http(config))
              },
              reject,
            })
          })
        }
      }
      case ResultCode.DISABLED: {
        ElNotification({
          title: '错误',
          message: response?.data?.message ?? '账号已被禁用',
          type: 'error',
        })
        await useUserStore().logout()
        break
      }
      default: {
        // 根据 HTTP 状态码和业务 code 区分通知类型
        let notificationType: 'success' | 'warning' | 'info' | 'error' = 'error'
        const code = response?.data?.code

        if (code >= 200 && code < 300) {
          notificationType = 'success'
        }
        else if (code >= 400 && code < 500) {
          notificationType = 'warning'
        }
        else if (code >= 500) {
          notificationType = 'error'
        }

        ElNotification({
          title: notificationType === 'error' ? '错误' : notificationType === 'warning' ? '警告' : '提示',
          message: response?.data?.message ?? '服务器错误',
          type: notificationType,
        })
        break
      }
    }

    return Promise.reject(response.data ? response.data : null)
  }
}

http.interceptors.response.use(
  handleResponse,
  async (error: any) => {
    isLoading.value = false
    if (['blob', 'arraybuffer'].includes(error.config?.responseType)) {
      const contentType = String(error.response?.headers['content-type'] ?? error.response?.data?.type ?? '')
      if (error.response && /\b(?:application|text)\/(?:[\w.-]+\+)?json\b/i.test(contentType)) {
        return handleResponse(error.response)
      }
      ElNotification({ title: '错误', message: '下载失败，请重试', type: 'error' })
      return Promise.reject(error)
    }
    const serverError = useDebounceFn(async () => {
      if (error && error.response && error.response.status === 500) {
        ElNotification({
          title: '服务器错误',
          message: error.message ?? '服务器错误',
          type: 'error',
        })
      }
    }, 3000, { maxWait: 5000 })
    await serverError()
    return Promise.reject(error)
  },
)

export default {
  http,
  createHttp,
}
