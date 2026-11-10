import { createElement, type ComponentType, type ReactNode } from 'react';

export type AuthKind = 'mini_program' | 'h5_register' | 'h5_login';
export type PluginSlot = 'user-profile-right' | 'user-center-menu';
export interface ProductShareOptions {
  channel: 'link' | 'card';
  spuId?: string | number;
  skuId?: string | number;
  activityId?: string | number;
  orderType?: string;
  sessionId?: string | number;
  groupNo?: string;
  groupBuyId?: string | number;
}
export interface ProductShare { path: string; h5Url: string }
export interface StorefrontPlugin {
  name: string;
  onAppEnter?: (entry: unknown) => void | Promise<void>;
  beforeAuth?: (kind: AuthKind, payload: Record<string, any>) => Promise<Record<string, any>>;
  afterAuth?: (result: any) => void;
  shareProduct?: (options: ProductShareOptions, current: ProductShare) => Promise<ProductShare>;
  slots?: Partial<Record<PluginSlot, ComponentType<any>>>;
  renderers?: Record<string, ComponentType<any>>;
}

const registered = new Map<string, StorefrontPlugin>();
export function registerStorefrontPlugin(plugin: StorefrontPlugin): void { registered.set(plugin.name, plugin); }
export function unregisterStorefrontPlugin(name: string): void { registered.delete(name); }

export async function notifyAppEntry(entry: unknown): Promise<void> {
  await Promise.allSettled([...registered.values()].map(async plugin => plugin.onAppEnter?.(entry)));
}
export async function prepareAuthPayload(kind: AuthKind, payload: Record<string, any>): Promise<Record<string, any>> {
  let result = payload;
  for (const plugin of registered.values()) {
    if (plugin.beforeAuth) result = await plugin.beforeAuth(kind, result);
  }
  return result;
}
export function notifyAuthResult(result: any): void {
  if (!result?.token) return;
  for (const plugin of registered.values()) plugin.afterAuth?.(result);
}
export function renderPluginSlot(slot: PluginSlot, props: Record<string, any> = {}): ReactNode {
  return [...registered.values()].map(plugin => {
    const Component = plugin.slots?.[slot];
    return Component ? createElement(Component, { ...props, key: plugin.name }) : null;
  });
}
export function hasPluginRenderer(type: string): boolean {
  return [...registered.values()].some(plugin => Boolean(plugin.renderers?.[type]));
}
export function renderPluginComponent(type: string, props: Record<string, any>): JSX.Element | null {
  for (const plugin of registered.values()) {
    const Component = plugin.renderers?.[type];
    if (Component) return createElement(Component, props);
  }
  return null;
}
export function buildProductSharePath(options: ProductShareOptions): string {
  const { channel: _channel, ...query } = options;
  const params = Object.entries(query).filter(([, value]) => value !== undefined && value !== '')
    .map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`).join('&');
  return `/pages/goods/details/index${params ? `?${params}` : ''}`;
}
export async function prepareProductShare(options: ProductShareOptions): Promise<ProductShare> {
  const path = buildProductSharePath(options);
  let result: ProductShare = { path, h5Url: '' };
  if (typeof window !== 'undefined') {
    const current = window.location.href;
    result.h5Url = current.includes('#') ? `${current.split('#')[0].split('?')[0]}#${path}` : `${window.location.origin}${path}`;
  }
  for (const plugin of registered.values()) {
    if (plugin.shareProduct) result = await plugin.shareProduct(options, result);
  }
  return result;
}
