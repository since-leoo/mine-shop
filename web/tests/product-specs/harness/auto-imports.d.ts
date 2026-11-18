export {}
declare global {
  const computed: typeof import('vue')['computed']
  const ref: typeof import('vue')['ref']
  const useHttp: typeof import('./http')['default']
  const useTrans: typeof import('./translation')['useTrans']
}
