import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ request: vi.fn() }));
vi.mock('@tarojs/taro', () => ({ default: {} }));
vi.mock('../../services/request', () => ({ request: mocks.request }));
import { resolveDistributionLanding } from './landing';

const token = '0123456789abcdef01234567';
beforeEach(() => { mocks.request.mockReset(); });

describe('小程序码落地', () => {
  it('解码短scene并匿名解析，恢复完整商品活动参数及来源', async () => {
    mocks.request.mockResolvedValue({ valid: true, target: 'product', query: {
      spuId: 123, skuId: 456, activityId: 789, sessionId: 10, groupBuyId: 11,
      orderType: 'group_buy', groupNo: 'G'.repeat(64),
    } });
    const result = await resolveDistributionLanding({ scene: encodeURIComponent(`s=${token}`) });
    expect(mocks.request).toHaveBeenCalledWith({ url: '/api/v1/distribution/share/resolve', data: { token } });
    expect(result.home).toBe(false);
    expect(result.path).toBe(`/pages/goods/details/index?spuId=123&skuId=456&activityId=789&orderType=group_buy&sessionId=10&groupNo=${'G'.repeat(64)}&groupBuyId=11&share_token=${token}`);
    expect(result.path.length).toBeGreaterThan(128);
  });

  it('业务停用或邀请满员仍允许浏览服务端商品，拒绝任意跳转路径和额外参数', async () => {
    mocks.request.mockResolvedValue({ valid: false, reason: 'disabled', target: 'product',
      path: 'https://untrusted.test', query: { spuId: 123, redirect: 'https://untrusted.test', memberId: 999, orderType: 'unknown' } });
    expect((await resolveDistributionLanding({ share_token: token })).path)
      .toBe(`/pages/goods/details/index?spuId=123&share_token=${token}`);
    mocks.request.mockResolvedValue({ valid: false, reason: 'full', target: 'home' });
    expect(await resolveDistributionLanding({ share_token: token }))
      .toEqual({ path: '/pages/home/index', home: true, shareToken: token });
  });

  it('过期分享提示返回首页，不继续携带失效邀请', async () => {
    mocks.request.mockResolvedValue({ valid: false, reason: 'expired' });
    expect(await resolveDistributionLanding({ scene: `s=${token}` }))
      .toEqual({ path: '/pages/home/index', home: true, shareToken: '', notice: '分享已过期，已返回首页' });
  });

  it('无效scene直接安全返回首页，网络失败保留给页面重试', async () => {
    expect((await resolveDistributionLanding({ scene: '%INVALID' })).home).toBe(true);
    expect(mocks.request).not.toHaveBeenCalled();
    mocks.request.mockRejectedValue(new Error('offline'));
    await expect(resolveDistributionLanding({ scene: `s=${token}` })).rejects.toThrow('offline');
  });
});
