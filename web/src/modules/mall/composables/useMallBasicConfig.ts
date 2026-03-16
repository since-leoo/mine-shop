/**
 * 商城配置 Composable
 * 用于获取商城系统配置
 */
import { ref } from 'vue'
import { systemSettingApi } from '@/modules/system/api/setting'

// 商城配置 key 列表
export const MALL_BASIC_KEYS = [
  'mall.basic.name',
  'mall.basic.admin_logo',
  'mall.basic.admin_small_logo',
  'mall.basic.login_logo',
  'mall.basic.miniapp_logo',
  'mall.basic.favicon',
  'mall.basic.logo',
] as const

export type MallBasicKey = typeof MALL[number]

export interface MallBasic_BASIC_KEYSConfig {
  'mall.basic.name': string
  'mall.basic.admin_logo': string
  'mall.basic.admin_small_logo': string
  'mall.basic.login_logo': string
  'mall.basic.miniapp_logo': string
  'mall.basic.favicon': string
  'mall.basic.logo': string
}

const configCache = ref<MallBasicConfig | null>(null)
const loading = ref(false)

export function useMallBasicConfig() {
  const fetchConfig = async (force = false): Promise<MallBasicConfig> => {
    if (configCache.value && !force) {
      return configCache.value
    }

    loading.value = true
    try {
      const res = await systemSettingApi.values([...MALL_BASIC_KEYS])
      configCache.value = res.data as MallBasicConfig
      return configCache.value
    }
    finally {
      loading.value = false
    }
  }

  const getLogo = async (type: 'admin' | 'admin_small' | 'login' | 'miniapp' | 'default' = 'admin'): Promise<string> => {
    const config = await fetchConfig()
    const keyMap: Record<typeof type, MallBasicKey> = {
      admin: 'mall.basic.admin_logo',
      admin_small: 'mall.basic.admin_small_logo',
      login: 'mall.basic.login_logo',
      miniapp: 'mall.basic.miniapp_logo',
      default: 'mall.basic.logo',
    }
    return config[keyMap[type]] || '/logo.svg'
  }

  const getName = async (): Promise<string> => {
    const config = await fetchConfig()
    return config['mall.basic.name'] || 'MineMall 商城'
  }

  const getFavicon = async (): Promise<string> => {
    const config = await fetchConfig()
    return config['mall.basic.favicon'] || '/favicon.ico'
  }

  const clearCache = () => {
    configCache.value = null
  }

  return {
    config: configCache,
    loading,
    fetchConfig,
    getLogo,
    getName,
    getFavicon,
    clearCache,
  }
}
