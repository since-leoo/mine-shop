import path from 'node:path'
import { defineConfig } from 'vite'
import jsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'

const webRoot = path.resolve(import.meta.dirname, '../..')
const root = path.join(import.meta.dirname, 'harness')

export default defineConfig({
  root,
  cacheDir: path.join(webRoot, 'node_modules/.vite-tests/export-download'),
  define: {
    'import.meta.env.VITE_APP_API_BASEURL': JSON.stringify('/'),
    'import.meta.env.VITE_OPEN_PROXY': JSON.stringify('false'),
    'import.meta.env.VITE_APP_STORAGE_PREFIX': JSON.stringify('export-test:'),
  },
  plugins: [jsx(), AutoImport({
    dts: false,
    imports: ['vue', {
      [path.join(root, 'runtime.ts')]: ['useUserStore', 'usePluginStore', 'useDayjs', 'useTrans'],
      [path.join(webRoot, 'src/hooks/auto-imports/useHttp.ts')]: [['default', 'useHttp']],
    }],
  })],
  server: { host: '127.0.0.1', port: 2994, strictPort: true, fs: { allow: [path.resolve(webRoot, '..')] } },
  resolve: {
    dedupe: ['vue', 'element-plus', 'vue-i18n'],
    alias: { '@': path.join(webRoot, 'src') },
  },
})
