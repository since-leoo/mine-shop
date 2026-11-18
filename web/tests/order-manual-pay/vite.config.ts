import fs from 'node:fs'
import path from 'node:path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { parse } from 'yaml'

const webRoot = path.resolve(import.meta.dirname, '../..')
const root = path.resolve(import.meta.dirname, 'harness')

export default defineConfig({
  cacheDir: path.resolve(import.meta.dirname, '../../node_modules/.vite-tests/order-manual-pay'),
  root,
  plugins: [vue()],
  define: {
    __ORDER_TEST_MESSAGES__: JSON.stringify(parse(fs.readFileSync(path.join(webRoot, 'src/locales/zh_CN[简体中文].yaml'), 'utf8'))),
  },
  server: { port: 2996, host: '127.0.0.1', fs: { allow: [path.resolve(webRoot, '..')] } },
  resolve: {
    dedupe: ['vue', 'element-plus', '@element-plus/icons-vue', 'vue-i18n'],
    alias: [
      { find: /^@\/hooks\/auto-imports\/useHttp(?:\.ts)?$/, replacement: path.join(root, 'http.ts') },
      { find: '@', replacement: path.join(webRoot, 'src') },
      { find: '~', replacement: path.join(webRoot, 'src/modules') },
    ],
  },
})
