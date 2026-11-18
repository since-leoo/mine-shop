import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import jsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'
import path from 'node:path'

const root = path.resolve(import.meta.dirname, 'harness')
const source = path.resolve(root, '../../../src')
const businessPages = new Set([
  'modules/base/views/welcome/index.vue',
  'modules/base/views/dashboard/workbench.vue',
  'modules/base/views/dashboard/analysis.vue',
  'modules/base/views/dashboard/report.vue',
  'modules/mall/views/diy/page/index.vue',
  'modules/mall/views/product/index.vue',
  'plugins/since/wechat/views/PluginPage.vue',
])
export default defineConfig({
  root,
  define: { 'import.meta.env.VITE_APP_STORAGE_PREFIX': JSON.stringify('plugin-tabs-test:') },
  plugins: [
    {
      name: 'plugin-tabs-business-pages',
      enforce: 'pre',
      load(id) {
        const relative = path.relative(source, id)
        if (businessPages.has(relative)) {
          return `<script lang="ts">import { createBusinessPage } from ${JSON.stringify(path.join(root, 'runtime.ts'))}; export default createBusinessPage(${JSON.stringify(relative.replace(/\W/g, '_'))})</script>`
        }
      },
    },
    vue(), jsx(), AutoImport({ dts: false, imports: ['vue', 'pinia', 'vue-router', {
      [path.join(root, 'settings.ts')]: [['default', 'useSettingStore']],
      [path.join(root, 'runtime.ts')]: ['useTrans'],
      [path.join(source, 'hooks/auto-imports/useDefaultSetting.ts')]: [['default', 'useDefaultSetting']],
      [path.join(source, 'store/modules/useKeepAliveStore.ts')]: [['default', 'useKeepAliveStore']],
      [path.join(source, 'store/modules/useIframeKeepAliveStore.ts')]: [['default', 'useIframeKeepAliveStore']],
    }] }),
  ],
  server: { host: '127.0.0.1', port: 4028, strictPort: true, fs: { allow: [path.resolve(root, '../../../../')] } },
  resolve: {
    dedupe: ['vue', 'pinia', 'vue-router', 'element-plus', 'sortablejs'],
    alias: [
      { find: /^@\/layouts$/, replacement: path.join(root, 'Layout.vue') },
      { find: '@/layouts/[...all].tsx', replacement: path.join(root, 'NotFound.vue') },
      { find: /^@\/store\/modules\/useSettingStore(?:\.ts)?$/, replacement: path.join(root, 'settings.ts') },
      { find: '@', replacement: source },
      { find: '~', replacement: path.join(source, 'modules') },
    ],
  },
})
