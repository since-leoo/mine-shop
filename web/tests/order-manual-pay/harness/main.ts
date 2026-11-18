import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import OrderList from '../../../src/modules/mall/views/order/index.vue'

declare const __ORDER_TEST_MESSAGES__: Record<string, any>

createApp(OrderList)
  .use(ElementPlus)
  .use(createI18n({ legacy: false, locale: 'zh-CN', messages: { 'zh-CN': __ORDER_TEST_MESSAGES__ } }))
  .directive('auth', {
    mounted(el, binding) {
      const allowed = new URLSearchParams(location.search).get('permission') !== 'denied'
        && binding.value.includes('order:order:manual-pay')
      if (!allowed) {
        el.remove()
      }
    },
  })
  .mount('#app')
