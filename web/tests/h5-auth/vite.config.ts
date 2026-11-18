import { fileURLToPath } from 'node:url'
import { resolve } from 'node:path'
import { defineConfig } from 'vite'

// 仅预览真实 Taro 构建产物，不载入后台主应用的 Vite 配置。
const dist = process.env.H5_DIST
  ? resolve(process.env.H5_DIST)
  : fileURLToPath(new URL('../../../miniprogram/dist', import.meta.url))

export default defineConfig({
  cacheDir: resolve(import.meta.dirname, '../../node_modules/.vite-tests/h5-auth'),
  build: { outDir: dist },
  preview: { host: '127.0.0.1', port: 3999, strictPort: true },
})
