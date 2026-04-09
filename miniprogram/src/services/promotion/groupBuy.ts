import { request } from '../request';

export interface OngoingGroup {
  groupNo: string;
  leaderNickname: string;
  leaderAvatar: string;
  joinedCount: number;
  needCount: number;
  expireTime: string;
}

function pickArray(res: any): any[] {
  if (Array.isArray(res)) return res;
  if (Array.isArray(res?.list)) return res.list;
  if (Array.isArray(res?.records)) return res.records;
  if (Array.isArray(res?.data)) return res.data;
  if (Array.isArray(res?.data?.list)) return res.data.list;
  if (Array.isArray(res?.data?.records)) return res.data.records;
  if (Array.isArray(res?.groups)) return res.groups;
  if (Array.isArray(res?.data?.groups)) return res.data.groups;
  return [];
}

export function fetchGroupBuyList(limit = 20) {
  return request({
    url: '/api/v1/group-buy/products',
    method: 'GET',
    data: { limit },
  });
}

export function fetchOngoingGroups(activityId: number | string, limit = 10): Promise<OngoingGroup[]> {
  return request({
    url: `/api/v1/group-buy/products/${activityId}/groups`,
    method: 'GET',
    data: { limit },
  }).then((res: any) => pickArray(res).map((g: any): OngoingGroup => ({
    groupNo: String(g.groupNo || g.groupId || g.id || ''),
    leaderNickname: String(g.leaderNickname || g.nickname || g.userName || g.leaderName || '拼团用户'),
    leaderAvatar: String(g.leaderAvatar || g.avatar || g.userAvatar || g.headUrl || ''),
    joinedCount: Number(g.joinedCount ?? g.joinCount ?? g.currentCount ?? g.currentUserCount ?? 0),
    needCount: Number(g.needCount ?? g.requireCount ?? g.targetCount ?? g.totalCount ?? 0),
    expireTime: String(g.expireTime || g.endTime || g.deadline || ''),
  })));
}

export function fetchGroupBuyProductDetail(activityId: number | string, spuId: number | string) {
  return request({
    url: `/api/v1/group-buy/products/${activityId}/${spuId}`,
    method: 'GET',
  });
}
