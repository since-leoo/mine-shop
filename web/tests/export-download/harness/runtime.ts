import { reactive } from 'vue'

export const user = reactive({
  token: 'initial-token',
  isLogin: true,
  logoutCount: 0,
  getLanguage: () => 'zh_CN',
  getPermissions: () => ['export:task:download'],
  async logout() {
    this.isLogin = false
    this.logoutCount++
  },
})

export const useUserStore = () => user
export const usePluginStore = () => ({ callHooks: async () => {} })
export const useDayjs = () => ({ unix: () => Math.floor(Date.now() / 1000) })
export const useTrans = () => ({ globalTrans: (key: string) => key })
