import { defineComponent, h, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

export const mounts: Record<string, number> = {}
export function useTrans(key: string) { return key === 'menu.welcome' ? '欢迎' : key }

/** Only business content is replaced; route identity and navigation use Vue Router. */
export function createBusinessPage(name: string) {
  return defineComponent({
    name,
    setup() {
      const route = useRoute()
      const router = useRouter()
      const mountNumber = ref(0)
      onMounted(() => { mountNumber.value = mounts[name] = (mounts[name] || 0) + 1 })
      return () => h('section', { 'data-testid': 'business-page', 'data-component': name, 'data-mount': mountNumber.value }, [
        h('p', { 'data-testid': 'business-url' }, route.fullPath),
        h('button', { onClick: () => router.push({ path: route.path, query: { tab: 'orders', status: 'pending' } }) }, '订单子页'),
        h('button', { onClick: () => router.push({ path: route.path, query: { tab: 'logs', page: '2' } }) }, '日志子页'),
        h(RouterLink, { to: '/plugin-center/since/wechat?tab=settings' }, () => '切到微信设置'),
        h(RouterLink, { to: '/plugin-center/since/supply-chain?tab=products' }, () => '切到供应链选品'),
      ])
    },
  })
}
