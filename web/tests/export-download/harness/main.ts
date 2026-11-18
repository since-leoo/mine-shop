import { createApp, h, ref } from 'vue'
import { createI18n } from 'vue-i18n'
import 'element-plus/dist/index.css'
import webColumns from '../../../src/plugins/since/export-center/views/task/data/getTableColumns'
import pluginColumns from '../../../../plugins/export-center/web/views/task/data/getTableColumns'
import useCache from '../../../src/hooks/useCache'
import { user } from './runtime'

if (new URLSearchParams(location.search).has('refresh')) {
  useCache().set('refresh_token', 'refresh-token')
}

createApp({
  setup() {
    const sources = { web: webColumns, plugin: pluginColumns }
    const statuses = ref<Record<string, string>>({ web: 'idle', plugin: 'idle' })
    const actions = Object.entries(sources).map(([name, columns]) => {
      const operation = columns({ value: undefined }).find(column => column.type === 'operation')!
      const action = operation.operationConfigure!.actions!.find(item => item.name === 'download')!
      return { name, action }
    })
    return () => h('main', [
      h('output', { 'data-testid': 'logout' }, user.logoutCount),
      ...actions.map(({ name, action }) => h('section', [
        h('button', {
          'data-testid': `${name}-download`,
          async onClick() {
            statuses.value[name] = 'pending'
            try {
              await (action.onClick as Function)({ row: { id: name === 'web' ? 7 : 8, file_name: 'fallback.csv' } })
              statuses.value[name] = 'success'
            }
            catch {
              statuses.value[name] = 'failed'
            }
          },
        }, `${name} download`),
        h('output', { 'data-testid': `${name}-status` }, statuses.value[name]),
      ])),
    ])
  },
})
  .use(createI18n({ legacy: false, locale: 'zh-CN', messages: {}, missingWarn: false, fallbackWarn: false }))
  .mount('#app')
