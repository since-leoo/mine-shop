import { createApp, defineComponent, h } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import Diy from './Diy.vue'
import Content from '../../../../plugins/distribution/web/views/Content.vue'
import Settings from '../../../../plugins/distribution/web/views/Settings.vue'
import distribution from '../../../../plugins/distribution/web/index.ts'
import PluginCenter from '../../../src/modules/plugin-center/views/index.vue'
import usePluginStore from '../../../src/store/modules/usePluginStore.ts'

const page = new URLSearchParams(location.search).get('page')
const app = createApp(page === 'diy' ? Diy : page === 'settings' ? Settings : page === 'shell' ? PluginCenter : Content)
app.use(createPinia()).use(ElementPlus)
app.component('ma-svg-icon', defineComponent({ render: () => h('span') }))
const router = createRouter({ history: createWebHistory(), routes: [{ path: '/:pluginName(.*)*', name: 'MinePluginCenterRoute', component: Content }] })
app.use(router)
app.config.globalProperties.$plugins = { 'since/distribution': distribution }
async function bootstrap() {
  await app.runWithContext(() => usePluginStore().registerPlugin(app))
  await router.isReady()
  app.mount('#app')
}
void bootstrap()
