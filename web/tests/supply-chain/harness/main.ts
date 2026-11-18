import { createApp, defineComponent, h } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import PluginPage from '../../../../plugins/supply-chain/web/views/PluginPage.vue'
import Connections from '../../../../plugins/supply-chain/web/views/Connections.vue'
import supplyChain from '../../../src/plugins/since/supply-chain/index.ts'
import PluginCenter from '../../../src/modules/plugin-center/views/index.vue'
import usePluginStore from '../../../src/store/modules/usePluginStore.ts'

const page = new URLSearchParams(location.search).get('page')
const app = createApp(page === 'settings' ? Connections : page === 'shell' ? PluginCenter : PluginPage)
app.use(createPinia()).use(ElementPlus)
app.component('ma-svg-icon', defineComponent({ render: () => h('span') }))
const router = createRouter({ history: createWebHistory(), routes: [{ path: '/:pluginName(.*)*', name: 'MinePluginCenterRoute', component: PluginPage }] })
app.use(router)
app.config.globalProperties.$plugins = { 'since/supply-chain': supplyChain }
async function bootstrap() {
  await app.runWithContext(() => usePluginStore().registerPlugin(app))
  await router.isReady()
  app.mount('#app')
}
void bootstrap()
