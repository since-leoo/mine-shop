import { request } from '../../services/request';
import { buildDistributionSharePath, parseDistributionInvite } from './share';

export interface DistributionLanding {
  path: string;
  home: boolean;
  shareToken: string;
  notice?: string;
}

const homePath = '/pages/home/index';

function positiveId(value: unknown): string | undefined {
  const id = typeof value === 'string' || typeof value === 'number' ? String(value) : '';
  return /^[1-9]\d{0,18}$/.test(id) ? id : undefined;
}

/** Resolve anonymous scans as well as scans by an existing signed-in customer. */
export async function resolveDistributionLanding(input: unknown): Promise<DistributionLanding> {
  const shareToken = parseDistributionInvite(input)?.shareToken || '';
  if (!/^[a-f0-9]{24}$/.test(shareToken)) {
    return { path: homePath, home: true, shareToken: '', notice: '分享标识无效，已返回首页' };
  }
  const result = await request({ url: '/api/v1/distribution/share/resolve', data: { token: shareToken } });
  if (result?.target === 'home') return { path: homePath, home: true, shareToken };
  if (result?.target === 'product') {
    const query = result.query || {};
    const spuId = positiveId(query.spuId || result.spuId);
    if (spuId) {
      // Ignore arbitrary paths and unknown parameters, even if returned by a stale server.
      const path = buildDistributionSharePath({
        target: 'product', channel: 'card', spuId,
        skuId: positiveId(query.skuId), activityId: positiveId(query.activityId),
        sessionId: positiveId(query.sessionId), groupBuyId: positiveId(query.groupBuyId),
        orderType: ['normal', 'seckill', 'group_buy'].includes(query.orderType) ? query.orderType : undefined,
        groupNo: typeof query.groupNo === 'string' && /^[A-Za-z0-9_-]{1,64}$/.test(query.groupNo) ? query.groupNo : undefined,
      }, shareToken);
      return { path, home: false, shareToken };
    }
  }
  return {
    path: homePath, home: true, shareToken: '',
    notice: result?.reason === 'expired' ? '分享已过期，已返回首页' : '分享暂不可用，已返回首页',
  };
}
