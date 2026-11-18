import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import App from './App.vue'

declare const __PRODUCT_TEST_MESSAGES__: Record<string, any>

createApp(App)
  .use(ElementPlus)
  .use(createI18n({ legacy: false, locale: 'zh-CN', messages: { 'zh-CN': __PRODUCT_TEST_MESSAGES__ } }))
  .component('MaSvgIcon', { template: '<span />' })
  .mount('#app')
