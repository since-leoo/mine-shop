import { afterEach, describe, expect, it, vi } from 'vitest';
import {
  buildProductSharePath, hasPluginRenderer, notifyAppEntry, notifyAuthResult,
  prepareAuthPayload, prepareProductShare, registerStorefrontPlugin,
  renderPluginComponent, renderPluginSlot, unregisterStorefrontPlugin,
} from './plugin-hooks';

afterEach(() => { unregisterStorefrontPlugin('test-a'); unregisterStorefrontPlugin('test-b'); });

describe('公共商城插件接口', () => {
  it('无插件时认证、商品分享保持基础行为', async () => {
    const payload = { phone: '13800138000', code: '123456' };
    await expect(prepareAuthPayload('h5_register', payload)).resolves.toEqual(payload);
    const options = { channel: 'card' as const, spuId: '17', activityId: 42, orderType: 'group_buy', groupNo: 'A&B' };
    expect(buildProductSharePath(options)).toBe('/pages/goods/details/index?spuId=17&activityId=42&orderType=group_buy&groupNo=A%26B');
    await expect(prepareProductShare(options)).resolves.toEqual({ path: buildProductSharePath(options), h5Url: '' });
    expect(hasPluginRenderer('optional-widget')).toBe(false);
    expect(renderPluginComponent('optional-widget', {})).toBeNull();
  });

  it('认证扩展按顺序执行，只有成功会员token触发登录后事件', async () => {
    const afterLogin = vi.fn();
    registerStorefrontPlugin({ name: 'test-a', beforeAuth: async (_kind, data) => ({ ...data, extension: 'first' }), afterAuth: afterLogin });
    registerStorefrontPlugin({ name: 'test-b', beforeAuth: async (_kind, data) => ({ ...data, checked: data.extension === 'first' }) });
    await expect(prepareAuthPayload('mini_program', { code: 'wx-code' })).resolves.toEqual({ code: 'wx-code', extension: 'first', checked: true });
    notifyAuthResult({ needsPhone: true });
    expect(afterLogin).not.toHaveBeenCalled();
    notifyAuthResult({ token: 'member-session' });
    expect(afterLogin).toHaveBeenCalledOnce();
  });

  it('一个可选启动监听器失败不阻断其他插件', async () => {
    const observe = vi.fn();
    registerStorefrontPlugin({ name: 'test-a', onAppEnter: () => { throw new Error('optional hook failed'); } });
    registerStorefrontPlugin({ name: 'test-b', onAppEnter: observe });
    const entry = { query: { page: '17' } };
    await expect(notifyAppEntry(entry)).resolves.toBeUndefined();
    expect(observe).toHaveBeenCalledWith(entry);
  });

  it('插件拥有页面插槽、DIY组件和分享扩展，卸载注册后全部移除', async () => {
    const Component = () => null;
    registerStorefrontPlugin({ name: 'test-a', slots: { 'user-profile-right': Component }, renderers: { 'optional-widget': Component }, shareProduct: async (_options, current) => ({ ...current, path: `${current.path}&extension=ok` }) });
    expect(hasPluginRenderer('optional-widget')).toBe(true);
    expect(renderPluginComponent('optional-widget', { value: 1 })?.props.value).toBe(1);
    expect(renderPluginSlot('user-profile-right', { value: 2 })).toEqual(expect.arrayContaining([expect.objectContaining({ type: Component })]));
    expect((await prepareProductShare({ channel: 'card', spuId: 17 })).path).toContain('extension=ok');
    unregisterStorefrontPlugin('test-a');
    expect(hasPluginRenderer('optional-widget')).toBe(false);
    expect(renderPluginSlot('user-profile-right')).toEqual([]);
    expect((await prepareProductShare({ channel: 'card', spuId: 17 })).path).not.toContain('extension');
  });
});
