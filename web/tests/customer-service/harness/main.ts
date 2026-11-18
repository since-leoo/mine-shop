import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import Configuration from '../../../../plugins/customer-service/web/views/configuration/index.vue'
import Workbench from '../../../../plugins/customer-service/web/views/workbench/index.vue'
import Faq from '../../../../plugins/customer-service/web/views/faq/index.vue'
const component = new URLSearchParams(location.search).get('page')
if (component === 'mobile') { void import('./mobile') }
else createApp(component === 'workbench' ? Workbench : component === 'faq' ? Faq : Configuration).use(ElementPlus).mount('#app')
