import { createApp, defineComponent, h, markRaw } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory, RouterView } from 'vue-router'
import { createI18n } from 'vue-i18n'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import usePluginStore from '@/store/modules/usePluginStore.ts'
import useRouteStore from '@/store/modules/useRouteStore.ts'
import useCache from '@/hooks/useCache.ts'
import { createBusinessPage } from './runtime'
import { settings } from './settings'
import './style.css'

const app = createApp({ render: () => h(RouterView) })
const router = createRouter({ history: createWebHistory(), routes: [] })
app.use(createPinia())
app.use(ElementPlus)
app.use(createI18n({ legacy: false, locale: 'zh-CN', messages: { 'zh-CN': { mineAdmin: { tab: {
  refresh: '刷新', close: '关闭', fixed: '固定', fullscreen: '全屏', closeOther: '关闭其它', closeLeft: '关闭左侧', closeRight: '关闭右侧',
} } } } }))
app.provide('defaultSetting', settings)
app.component('ma-svg-icon', defineComponent({
  props: ['name', 'size'],
  setup: props => () => h('span', { 'data-icon': props.name, 'aria-hidden': 'true' }, props.name.includes('close') ? '×' : '●'),
}))

const makePlugin = (name: string, component: string, extra: Record<string, unknown> = {}) => ({
  install() {},
  config: { enable: true, info: { name, version: '1.0.0', author: 'integration test' } },
  center: { title: name, page: markRaw(createBusinessPage(component)) },
  ...extra,
})
app.config.globalProperties.$plugins = {
  'since/supply-chain': makePlugin('供应链', 'SupplyFixture'),
  'since/wechat': makePlugin('微信', 'WechatFixture'),
  'test/legacy': makePlugin('旧插件', 'LegacyFixture', {
    views: [{ name: 'LegacyViews', path: '/views-plugin/:id', component: markRaw(createBusinessPage('ViewsFixture')), meta: { title: '声明入口', cache: true }, children: [
      { name: 'LegacyViewsChild', path: '/views-plugin/:id/detail', component: markRaw(createBusinessPage('ViewsChildFixture')), meta: { title: '声明子页', cache: true } },
    ] }],
    hooks: { registerRoute(target: typeof router) {
      target.addRoute('MineRootLayoutRoute', { name: 'HookPlugin', path: '/hook-plugin/:id', component: markRaw(createBusinessPage('HookFixture')), meta: { title: 'Hook 入口', cache: true } })
    } },
  }),
}
await app.runWithContext(async () => { await usePluginStore().registerPlugin(app) })
const routeStore = app.runWithContext(() => useRouteStore())
await routeStore.initRoutes(router, [
  { name: 'Item', path: '/items/:id', component: 'mall/views/product/index', meta: { title: '普通商品', type: 'M', cache: true } },
  { name: 'Other', path: '/other', component: 'mall/views/product/index', meta: { title: '其它页面', type: 'M' } },
  { name: 'LegacyWechat', path: '/legacy-wechat', component: 'since/wechat/views/PluginPage', meta: { title: '微信旧菜单', type: 'M', cache: true } },
  { name: 'LegacyDiy', path: '/legacy-diy', component: 'mall/views/diy/page/index', meta: { title: '装修旧菜单', type: 'M', cache: true } },
  { name: 'LegacyWorkbench', path: '/legacy-workbench', component: 'base/views/dashboard/workbench', meta: { title: '工作台旧菜单', type: 'M', cache: true } },
])
if ((window as any).__seedTabs) useCache().set('tabList', (window as any).__seedTabs)
app.use(router)
await router.isReady()
app.mount('#app')
