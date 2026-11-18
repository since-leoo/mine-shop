import { useI18n } from 'vue-i18n'

export function useTrans() {
  return { globalTrans: useI18n().t }
}
