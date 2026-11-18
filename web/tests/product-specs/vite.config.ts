import fs from 'node:fs'
import path from 'node:path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import { parse } from 'yaml'

const webRoot = path.resolve(import.meta.dirname, '../..')
const root = path.resolve(import.meta.dirname, 'harness')
export default defineConfig({
  cacheDir: path.resolve(import.meta.dirname, '../../node_modules/.vite-tests/product-specs'),
  root,
  plugins: [vue(), AutoImport({ dts: false, imports: ['vue', { [path.join(root, 'http.ts')]: [['default', 'useHttp']], [path.join(root, 'translation.ts')]: ['useTrans'] }] })],
  define: { __PRODUCT_TEST_MESSAGES__: JSON.stringify(parse(fs.readFileSync(path.join(webRoot, 'src/locales/zh_CN[简体中文].yaml'), 'utf8'))) },
  server: { host: '127.0.0.1', port: 4027, strictPort: true, fs: { allow: [path.resolve(webRoot, '..')] } },
  resolve: {
    dedupe: ['vue', 'element-plus', '@element-plus/icons-vue', 'vue-i18n'],
    alias: [
      { find: /^@\/hooks\/auto-imports\/useHttp(?:\.ts)?$/, replacement: path.join(root, 'http.ts') },
      // These media controls are unrelated to specs; the actual product form and Element Plus inputs run unchanged.
      { find: /^@\/components\/ma-(?:upload-image|rich-editor)\/index.vue$/, replacement: path.join(root, 'MediaStub.vue') },
      { find: '@', replacement: path.join(webRoot, 'src') },
      { find: '~', replacement: path.join(webRoot, 'src/modules') },
    ],
  },
})
