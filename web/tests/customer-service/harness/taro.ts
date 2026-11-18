import { useEffect } from 'react'
export const useDidShow = (callback: () => void) => useEffect(callback, [])
export const useDidHide = (callback: () => void) => useEffect(() => callback, [])
export default { getEnv: () => 'WEAPP', ENV_TYPE: { WEB: 'WEB', WEAPP: 'WEAPP' }, getStorageSync: (key: string) => key === 'accessToken' ? 'fixture-member-token' : '', connectSocket: async () => new (window as any).__MiniSocket(), showToast: async () => {}, showModal: async () => ({ confirm: true }), chooseImage: async () => ({ tempFilePaths: ['/tmp/example.jpg'], tempFiles: [{ size: 100 }] }), showLoading: async () => {}, hideLoading: async () => {}, previewImage: async () => {}, navigateTo: async (options: any) => { (window as any).__navigation = options.url } }
