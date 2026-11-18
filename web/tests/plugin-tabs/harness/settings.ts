import { defineStore } from 'pinia'
import { ref } from 'vue'

export const settings = {
  welcomePage: { name: 'welcome', path: '/welcome', title: '欢迎', icon: 'carbon:home' },
  app: { whiteRoute: [], colorMode: 'light' },
  tabbar: { mode: 'rectangle', enable: true },
}
export default defineStore('plugin-tabs-test-settings', () => {
  const title = ref('')
  return {
    title,
    getSettings: (key?: keyof typeof settings) => key ? settings[key] : settings,
    setTitle: (value: string) => { title.value = value },
  }
})
