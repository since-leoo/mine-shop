/**
 * 插件前端菜单类型扩展
 * 
 * @description 此文件定义了 PluginMenu 接口，用于支持插件在前端定义菜单结构
 */

import type { App } from 'vue'
import type { Router } from 'vue-router'
import type { MineRoute, Plugin, Route } from './global'

/**
 * 插件菜单类型
 * 用于定义不需要组件的父菜单、菜单分组等
 * 与 MineRoute.routeRecord 保持一致的结构
 */
export interface PluginMenu {
  /** 菜单名称（唯一标识） */
  name?: string
  /** 菜单路径 */
  path?: string
  /** 重定向路径 */
  redirect?: string
  /** 是否展开（用于菜单分组） */
  expand?: boolean
  /** 菜单元数据 */
  meta?: MineRoute.RouteMeta
  /** 子菜单 */
  children?: PluginMenu[]
}

/**
 * 带有菜单配置的插件配置类型
 * 包含 Plugin.PluginConfig 的所有属性，并添加了 menus 属性
 */
export interface PluginConfigWithMenus {
  install: (app: App) => void
  config: Plugin.Config
  views?: Plugin.Views[]
  /**
   * 插件菜单配置
   * 用于定义纯菜单结构（父菜单、菜单分组）
   * 与 views 配合使用：menus 定义菜单结构，views 定义路由组件
   */
  menus?: PluginMenu[]
  /**
   * 插件hooks
   * 插件禁用时，定义的hook不会被触发
   */
  hooks?: {
    start?: (config: Plugin.Config) => any | void
    setup?: () => any | void
    registerRoute?: (router: Router, routesRaw: Route.RouteRecordRaw[] | Plugin.Views[] | MineRoute.routeRecord[]) => any | void
    loginBefore?: (data: Record<string, any>) => any | void
    login?: (formInfo: any) => any | void
    logout?: () => any | void
    getUserInfo?: (userInfo: any) => any | void
    routerRedirect?: (params: { oldRoute: any, newRoute: any }, router: Router) => any | void
    networkRequest?: (request: any) => any | void
    networkResponse?: (response: any) => any | void
  }
  [key: string]: any
}
