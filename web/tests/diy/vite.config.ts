import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import jsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'
import path from 'node:path'

const root = path.resolve(import.meta.dirname, 'harness')
const source = path.resolve(root, '../../../src')
export default defineConfig({
  cacheDir: path.resolve(import.meta.dirname, '../../node_modules/.vite-tests/diy'),
  root,
  plugins: [vue(), jsx(), AutoImport({
    dts: false,
    imports: ['vue', 'pinia', {
      [path.join(root, 'http.ts')]: [['default', 'useHttp']],
      [path.join(source, 'hooks/auto-imports/useDefaultSetting.ts')]: [['default', 'useDefaultSetting']],
      [path.join(root, 'runtime.ts')]: ['useTrans', 'useUserStore'],
    }],
  })],
  server: { host: '127.0.0.1', port: 4001, strictPort: true, fs: { allow: [path.resolve(root, '../../../../')] } },
  resolve: {
    dedupe: ['vue', 'pinia', 'vue-router', 'element-plus', '@element-plus/icons-vue', 'lodash-es', 'sortablejs'],
    alias: [
      { find: /^@\/layouts$/, replacement: path.join(root, 'Layout.vue') },
      { find: '@/layouts/[...all].tsx', replacement: path.join(root, 'NotFound.vue') },
      { find: /^@\/hooks\/auto-imports\/useHttp(?:\.ts)?$/, replacement: path.join(root, 'http.ts') },
      { find: '@/hooks/useDialog.ts', replacement: path.join(root, 'dialog.ts') },
      { find: '@/components/ma-upload-image/index.vue', replacement: path.join(root, 'Empty.vue') },
      { find: '@', replacement: source },
      { find: '~', replacement: path.join(source, 'modules') },
    ],
  },
})
