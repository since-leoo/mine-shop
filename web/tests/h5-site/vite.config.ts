import { resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vite'

export default defineConfig({
  cacheDir: resolve(import.meta.dirname, '../../node_modules/.vite-tests/h5-site'),
  build: { outDir: process.env.H5_DIST ? resolve(process.env.H5_DIST) : fileURLToPath(new URL('../../../miniprogram/dist', import.meta.url)) },
  preview: { host: '127.0.0.1', port: 4003, strictPort: true },
})
