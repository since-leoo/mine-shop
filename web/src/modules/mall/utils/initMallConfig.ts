/**
 * 商城配置加载工具
 * 在应用启动时预加载商城基础配置
 */
import { useMallBasicConfig } from '@/modules/mall/composables/useMallBasicConfig'

/**
 * 初始化商城基础配置
 * 应该在用户登录成功后调用
 */
export async function initMallBasicConfig() {
  try {
    const { getFavicon, getName, fetchConfig } = useMallBasicConfig()

    // 预加载配置
    await fetchConfig()

    // 更新 favicon
    const favicon = await getFavicon()
    updateFavicon(favicon)

    // 更新页面标题
    const name = await getName()
    document.title = name

    return true
  }
  catch (e) {
    console.error('Failed to init mall config:', e)
    return false
  }
}

/**
 * 更新浏览器 favicon
 */
function updateFavicon(url: string) {
  // 查找现有的 favicon link
  let faviconLink = document.querySelector<HTMLLinkElement>('link[rel="shortcut icon"]')

  if (!faviconLink) {
    // 如果不存在，创建一个
    faviconLink = document.createElement('link')
    faviconLink.rel = 'shortcut icon'
    document.head.appendChild(faviconLink)
  }

  // 添加时间戳避免缓存
  const timestamp = Date.now()
  const urlWithTimestamp = url.includes('?')
    ? `${url}&t=${timestamp}`
    : `${url}?t=${timestamp}`

  faviconLink.href = urlWithTimestamp
}
