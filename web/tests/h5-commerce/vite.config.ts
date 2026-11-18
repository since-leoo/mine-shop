import { fileURLToPath } from 'node:url'
import { resolve } from 'node:path'
import { defineConfig } from 'vite'

const dist = process.env.H5_DIST ? resolve(process.env.H5_DIST) : fileURLToPath(new URL('../../../miniprogram/dist', import.meta.url))
export default defineConfig({ cacheDir: resolve(import.meta.dirname, '../../node_modules/.vite-tests/h5-commerce'), build: { outDir: dist }, preview: { host: '127.0.0.1', port: 4002, strictPort: true } })
