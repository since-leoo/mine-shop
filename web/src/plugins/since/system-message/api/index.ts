/**
 * API统一导出
 */
export * from './message'
export * from './template'
export * from './preference'

// 重新导出常用的API
export { messageAdminApi, messageUserApi, messagePublicApi } from './message'
export { templateApi } from './template'
export { preferenceApi, preferenceUtils } from './preference'