export {}

// The real plugin center relies on Vite's Vue/Pinia auto imports.
declare global {
  const computed: typeof import('vue')['computed']
  const ref: typeof import('vue')['ref']
  const defineStore: typeof import('pinia')['defineStore']
}
