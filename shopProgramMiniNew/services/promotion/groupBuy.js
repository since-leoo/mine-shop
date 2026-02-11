import { request } from '../request';

/**
 * 获取拼团活动商品列表
 */
export function fetchGroupBuyList(limit = 20) {
  return request({
    url: '/api/v1/group-buy/products',
    method: 'GET',
    data: { limit },
  });
}

/**
 * 获取拼团活动正在进行中的团列表
 */
export function fetchOngoingGroups(activityId, limit = 10) {
  return request({
    url: `/api/v1/group-buy/products/${activityId}/groups`,
    method: 'GET',
    data: { limit },
  }).then((res) => res?.list || []);
}
