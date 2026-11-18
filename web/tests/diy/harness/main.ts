import { createApp, defineComponent, h } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory, RouterView } from 'vue-router'
import { createI18n } from 'vue-i18n'
import ElementPlus from 'element-plus'
import MaForm from '@mineadmin/form'
import MaTable from '@mineadmin/table'
import MaSearch from '@mineadmin/search'
import MaProTable from '@mineadmin/pro-table'
import 'element-plus/dist/index.css'
import '@mineadmin/table/dist/style.css'
import '@mineadmin/search/dist/style.css'
import '@mineadmin/pro-table/dist/style.css'
import diy from '@/plugins/since/diy/index.ts'
import customerService from '@/plugins/since/customer-service/index.ts'
import exportCenter from '@/plugins/since/export-center/index.ts'
import distribution from '@/plugins/since/distribution/index.ts'
import supplyChain from '@/plugins/since/supply-chain/index.ts'
import usePluginStore from '@/store/modules/usePluginStore.ts'
import useRouteStore from '@/store/modules/useRouteStore.ts'

const app = createApp({ render: () => h(RouterView) })
const router = createRouter({ history: createWebHistory(), routes: [] })
export const i18n = createI18n({ legacy: false, locale: 'zh_CN', fallbackLocale: 'zh_CN' })
app.use(createPinia())
app.use(i18n)
app.use(ElementPlus)
app.use(MaTable).use(MaForm).use(MaSearch).use(MaProTable, { ssr: false, provider: { app } })
app.component('ma-svg-icon', defineComponent({ render: () => h('span') }))
app.directive('auth', {})
app.provide('defaultSetting', { welcomePage: { name: 'welcome', path: '/welcome', title: '欢迎', icon: '' } })
// Load real plugin entries with Composition i18n, matching production startup.
// No DIY route is declared here: registration must come from the real plugin and route store.
app.config.globalProperties.$plugins = {
  'since/customer-service': customerService,
  'since/export-center': exportCenter,
  'since/distribution': distribution,
  'since/supply-chain': supplyChain,
  'since/diy': diy,
}
await app.runWithContext(async () => {
  await usePluginStore().registerPlugin(app, { i18n: i18n.global })
})
const routeStore = app.runWithContext(() => useRouteStore())
await routeStore.initRoutes(router, [])
app.use(router)
await router.isReady()
app.mount('#app')
