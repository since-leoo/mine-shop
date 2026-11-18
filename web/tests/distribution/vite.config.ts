import path from 'node:path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'

const root = path.resolve(import.meta.dirname, 'harness')
export default defineConfig({ cacheDir: path.resolve(import.meta.dirname, '../../node_modules/.vite-tests/distribution'), root, plugins: [vue(), AutoImport({ dts: false, imports: ['vue', 'pinia', { [path.join(root, 'http.ts')]: [['default', 'useHttp']] }] })], server: { host: '127.0.0.1', port: 4012, strictPort: true, fs: { allow: [path.resolve(root, '../../../../')] } }, resolve: { dedupe: ['vue', 'pinia', 'vue-router', 'element-plus', '@element-plus/icons-vue', 'sortablejs'], alias: [{ find: '@/components/ma-upload-image/index.vue', replacement: path.join(root, 'Empty.vue') }, { find: '~', replacement: path.resolve(root, '../../../src/modules') }, { find: /^@\/hooks\/auto-imports\/useHttp(?:\.ts)?$/, replacement: path.join(root, 'http.ts') }, { find: /^@\/utils\/permission\/hasRole(?:\.ts)?$/, replacement: path.join(root, 'permission.ts') }, { find: '@', replacement: path.resolve(root, '../../../src') }] } })
