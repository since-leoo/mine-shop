import path from 'node:path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'

const root = path.resolve(import.meta.dirname, 'harness')
export default defineConfig({
  cacheDir: path.resolve(import.meta.dirname, '../../node_modules/.vite-tests/supply-chain'),
  root,
  plugins: [vue(), AutoImport({ dts: false, imports: ['vue', 'pinia'] })],
  server: { host: '127.0.0.1', port: 4026, strictPort: true, fs: { allow: [path.resolve(root, '../../../../')] } },
  resolve: {
    dedupe: ['vue', 'pinia', 'vue-router', 'element-plus', '@element-plus/icons-vue'],
    alias: [
      { find: /^@\/hooks\/auto-imports\/useHttp(?:\.ts)?$/, replacement: path.join(root, 'http.ts') },
      { find: /^@\/utils\/permission\/hasAuth(?:\.ts)?$/, replacement: path.join(root, 'permission.ts') },
      { find: '@', replacement: path.resolve(root, '../../../src') },
    ],
  },
})
