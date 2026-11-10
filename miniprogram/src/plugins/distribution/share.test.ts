import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
  storage: new Map<string, any>(),
  request: vi.fn(),
  getEnterOptionsSync: vi.fn(),
  showToast: vi.fn(),
  getEnv: vi.fn(),
}));

vi.mock('@tarojs/taro', () => ({
  default: {
    getStorageSync: (key: string) => mocks.storage.get(key),
    setStorageSync: (key: string, value: any) => mocks.storage.set(key, value),
    removeStorageSync: (key: string) => mocks.storage.delete(key),
    getEnterOptionsSync: mocks.getEnterOptionsSync,
    getEnv: mocks.getEnv,
    showToast: mocks.showToast,
    ENV_TYPE: { WEAPP: 'WEAPP' },
  },
}));
vi.mock('../../services/request', () => ({ request: mocks.request }));
vi.mock('./components/UserCenterEntry', () => ({ DistributionProfileEntry: () => null, DistributionMenuEntry: () => null }));
vi.mock('./components/DistributionCenter', () => ({ default: () => null }));

import {
  buildDistributionH5Url,
  buildDistributionSharePath,
  captureDistributionInvite,
  clearDistributionInvite,
  createDistributionShare,
  getDistributionInviteForRegistration,
  parseDistributionInvite,
} from './share';
import { miniProgramLogin } from '../../services/auth/login';
import { passwordLogin, registerByPhone, smsLogin } from '../../services/auth/h5Auth';
import { prepareProductShare, registerStorefrontPlugin } from '../../common/plugin-hooks';
import distribution from './index';

beforeEach(() => {
  registerStorefrontPlugin(distribution);
  clearDistributionInvite();
  mocks.storage.clear();
  mocks.request.mockReset();
  mocks.getEnterOptionsSync.mockReset();
  mocks.getEnterOptionsSync.mockReturnValue({ query: {} });
  mocks.getEnv.mockReturnValue('WEAPP');
  mocks.showToast.mockReset();
  vi.useRealTimers();
});

describe('邀请入口捕获', () => {
  it('解析 H5 hash、普通 query、小程序 query 和编码 scene，拒绝原始会员 ID', () => {
    expect(parseDistributionInvite('https://shop.test/#/pages/home/index?share_token=share_123')).toEqual({ shareToken: 'share_123' });
    expect(parseDistributionInvite('https://shop.test/pages/home/index?invite_code=ABCD1234')).toEqual({ inviteCode: 'ABCD1234' });
    expect(parseDistributionInvite({ query: { scene: encodeURIComponent('share_token=scene_123') } })).toEqual({ shareToken: 'scene_123' });
    expect(parseDistributionInvite({ query: { invite_code: 'ABCD1234' } })).toEqual({ inviteCode: 'ABCD1234' });
    expect(parseDistributionInvite({ query: { member_id: '18', inviter_id: '19' } })).toBeNull();
    expect(parseDistributionInvite({ query: { scene: '%INVALID' } })).toBeNull();
  });

  it('并发进入时等待首个有效来源，后续分享不能覆盖', async () => {
    let finish!: (value: any) => void;
    mocks.request.mockImplementationOnce(() => new Promise((resolve) => { finish = resolve; }));
    const first = captureDistributionInvite({ query: { share_token: 'first_123' } });
    const second = captureDistributionInvite({ query: { share_token: 'second_456' } });
    let loginReady = false;
    const registration = getDistributionInviteForRegistration().then((result) => { loginReady = true; return result; });
    await Promise.resolve();
    expect(loginReady).toBe(false);
    finish({ valid: true });
    await Promise.all([first, second]);
    await expect(registration).resolves.toEqual({ shareToken: 'first_123' });
    expect(mocks.request).toHaveBeenCalledTimes(1);
  });

  it('伪造或失效的首个来源不会占据邀请归因', async () => {
    mocks.request.mockResolvedValueOnce({ valid: false, reason: 'expired' }).mockResolvedValueOnce({ valid: true });
    await captureDistributionInvite({ share_token: 'invalid_123' });
    await captureDistributionInvite({ invite_code: 'ABCD1234' });
    await expect(getDistributionInviteForRegistration()).resolves.toEqual({ inviteCode: 'ABCD1234' });
    expect(mocks.request).toHaveBeenLastCalledWith({ url: '/api/v1/distribution/share/resolve', data: { inviteCode: 'ABCD1234' } });
  });

  it('网络失败保留待验证来源，登录时重新验证', async () => {
    mocks.request.mockRejectedValueOnce(new Error('offline')).mockResolvedValueOnce({ valid: true });
    await captureDistributionInvite({ share_token: 'retry_123' });
    await expect(getDistributionInviteForRegistration()).resolves.toEqual({ shareToken: 'retry_123' });
    expect(mocks.request).toHaveBeenCalledTimes(2);
  });

  it('七天后过期，不在重复打开时延长归因时间', async () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-10-16T00:00:00Z'));
    mocks.request.mockResolvedValue({ valid: true });
    await captureDistributionInvite({ share_token: 'expires_123' });
    vi.setSystemTime(new Date('2026-10-22T00:00:00Z'));
    await captureDistributionInvite({ share_token: 'expires_123' });
    vi.setSystemTime(new Date('2026-10-23T00:00:01Z'));
    await expect(getDistributionInviteForRegistration()).resolves.toEqual({});
    expect(mocks.storage.has('distributionPendingInvite')).toBe(false);
  });

  it('已登录用户不记录新邀请，清理后正在解析的旧结果也不能回写', async () => {
    mocks.storage.set('accessToken', 'session');
    await captureDistributionInvite({ share_token: 'ignored_123' });
    expect(mocks.request).not.toHaveBeenCalled();
    mocks.storage.delete('accessToken');
    let finish!: (value: any) => void;
    mocks.request.mockImplementation(() => new Promise((resolve) => { finish = resolve; }));
    const pending = captureDistributionInvite({ share_token: 'pending_123' });
    await Promise.resolve();
    clearDistributionInvite();
    finish({ valid: true });
    await pending;
    await expect(getDistributionInviteForRegistration()).resolves.toEqual({});
  });
});

describe('分享身份与目标', () => {
  it('重新分享向鉴权接口领取当前用户身份，同时保留商品和活动参数', async () => {
    mocks.storage.set('accessToken', 'current-user-session');
    mocks.request.mockResolvedValue({ shareToken: 'current_123', path: '/untrusted-input-is-not-reused' });
    const result = await createDistributionShare({
      target: 'product', channel: 'card', spuId: 18, skuId: 19, orderType: 'group_buy',
      activityId: 20, sessionId: 21, groupBuyId: 22, groupNo: 'GROUP A&B',
    });
    expect(result.path).toBe('/pages/goods/details/index?spuId=18&skuId=19&activityId=20&orderType=group_buy&sessionId=21&groupNo=GROUP%20A%26B&groupBuyId=22&share_token=current_123');
    expect(mocks.request).toHaveBeenCalledWith(expect.objectContaining({
      url: '/api/v1/distribution/share', needAuth: true,
      data: expect.objectContaining({ platform: 'mini_program', channel: 'card' }),
    }));
  });

  it('未登录或插件关闭时分享普通商品，不能继承收到的邀请码', async () => {
    const options = { target: 'product' as const, channel: 'link' as const, spuId: '18' };
    expect((await createDistributionShare(options)).path).toBe('/pages/goods/details/index?spuId=18');
    expect(mocks.request).not.toHaveBeenCalled();
    mocks.storage.set('accessToken', 'session');
    mocks.request.mockResolvedValue({ enabled: false, shareToken: '' });
    expect((await createDistributionShare(options)).path).toBe('/pages/goods/details/index?spuId=18');
    expect(buildDistributionH5Url('/pages/goods/details/index?spuId=18', 'https://shop.test/?invite_code=OLD123#/pages/home/index?share_token=old_123'))
      .toBe('https://shop.test/#/pages/goods/details/index?spuId=18');
  });

  it('支持 H5 hash 和 history URL，分享参数经过编码', () => {
    const path = buildDistributionSharePath({ target: 'product', channel: 'link', spuId: 'A&B' }, 'owner_123');
    expect(buildDistributionH5Url(path, 'https://shop.test/pages/home/index?share_token=old_123')).toBe(`https://shop.test${path}`);
    expect(buildDistributionH5Url(path, 'https://shop.test/app/#/pages/home/index')).toBe(`https://shop.test/app/#${path}`);
  });

  it('H5 二维码通过本地编码器生成可展示的 SVG，不调用第三方二维码服务', async () => {
    mocks.storage.set('accessToken', 'session');
    mocks.getEnv.mockReturnValue('WEB');
    mocks.request.mockResolvedValue({ enabled: true, shareToken: 'owner_123', h5Url: 'https://shop.test/#/pages/home/index?share_token=owner_123' });
    const result = await createDistributionShare({ target: 'home', channel: 'qrcode' });
    expect(result.imageUrl).toMatch(/^data:image\/svg\+xml;charset=utf-8,/);
    const svg = decodeURIComponent(result.imageUrl!.split(',')[1]);
    expect(svg).toContain('<svg');
    expect(svg).toContain('width="320"');
    expect(svg).toContain('<path');
  });

  it('微信码相对地址使用后端API域名，未配置H5域名时不提供伪链接', async () => {
    mocks.storage.set('accessToken', 'session');
    mocks.request.mockResolvedValue({ shareToken: 'owner_123', h5Url: '', imageUrl: '/api/v1/distribution/share/images/code.png' });
    const result = await createDistributionShare({ target: 'home', channel: 'qrcode' });
    expect(result.imageUrl).toBe('http://127.0.0.1:9501/api/v1/distribution/share/images/code.png');
    expect(result.h5Url).toBe('');
  });

  it('后端插件未安装返回404时保留普通商品分享', async () => {
    mocks.storage.set('accessToken', 'session');
    mocks.request.mockRejectedValue({ statusCode: 404, code: 404 });
    await expect(prepareProductShare({ channel: 'card', spuId: 18, activityId: 42 }))
      .resolves.toEqual({ path: '/pages/goods/details/index?spuId=18&activityId=42', h5Url: '' });
  });
});

describe('登录与注册邀请传参', () => {
  it('小程序首次登录等待启动邀请校验，成功后消费来源', async () => {
    mocks.getEnterOptionsSync.mockReturnValue({ query: { scene: 'share_token=mini_123' } });
    mocks.request.mockResolvedValueOnce({ valid: true }).mockResolvedValueOnce({ token: 'new-session' });
    await miniProgramLogin({ code: 'wechat-code' });
    expect(mocks.request).toHaveBeenLastCalledWith(expect.objectContaining({
      url: '/api/v1/login/miniApp', data: expect.objectContaining({ code: 'wechat-code', shareToken: 'mini_123' }),
    }));
    expect(mocks.storage.has('distributionPendingInvite')).toBe(false);
  });

  it('H5 注册失败保留邀请，重试成功后清理', async () => {
    mocks.request.mockResolvedValueOnce({ valid: true });
    await captureDistributionInvite({ invite_code: 'ABCD1234' });
    const form = { phone: '13800138000', password: 'secret', passwordConfirmation: 'secret', code: '123456' };
    mocks.request.mockRejectedValueOnce(new Error('验证码错误'));
    await expect(registerByPhone(form)).rejects.toThrow('验证码错误');
    expect(mocks.storage.has('distributionPendingInvite')).toBe(true);
    mocks.request.mockResolvedValueOnce({ token: 'new-session' });
    await registerByPhone(form);
    expect(mocks.request).toHaveBeenLastCalledWith(expect.objectContaining({
      url: '/api/v1/auth/register', data: { ...form, inviteCode: 'ABCD1234' },
    }));
    expect(mocks.storage.has('distributionPendingInvite')).toBe(false);
  });

  it.each(['password', 'sms'])('已有用户通过 %s 登录后不保留他人的邀请来源', async (kind) => {
    mocks.request.mockResolvedValue({ valid: true });
    await captureDistributionInvite({ invite_code: 'ABCD1234' });
    mocks.request.mockResolvedValueOnce({ token: 'existing-session' });
    if (kind === 'password') await passwordLogin('13800138000', 'secret');
    else await smsLogin('13800138000', '123456');
    expect(mocks.storage.has('distributionPendingInvite')).toBe(false);
  });

  it('邀请满员仍能注册，保留来源供后端反馈并提示归属平台', async () => {
    mocks.request.mockResolvedValueOnce({ valid: false, reason: 'full' });
    await captureDistributionInvite({ share_token: 'full_123' });
    mocks.request.mockResolvedValueOnce({ valid: false, reason: 'full' });
    mocks.request.mockResolvedValueOnce({ token: 'new-session', referralBinding: { bound: false, reason: 'full' } });
    await expect(miniProgramLogin({ code: 'wechat-code' })).resolves.toEqual(expect.objectContaining({ token: 'new-session' }));
    expect(mocks.request).toHaveBeenLastCalledWith(expect.objectContaining({ data: expect.objectContaining({ shareToken: 'full_123' }) }));
    expect(mocks.showToast).toHaveBeenCalledWith(expect.objectContaining({ title: expect.stringContaining('归属平台') }));
    expect(mocks.storage.has('distributionPendingInvite')).toBe(false);
  });
});
