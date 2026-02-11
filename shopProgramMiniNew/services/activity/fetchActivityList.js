import { request } from '../request';
import { config } from '../../config/index';

/**
 * 获取拼团活动列表（小程序活动页用）
 */
export function fetchActivityList(pageIndex = 1, pageSize = 20) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getActivityList } = require('../../model/activities');
    return delay().then(() => getActivityList(pageIndex, pageSize));
  }
  return request({
    url: '/api/v1/group-buy/products',
    method: 'GET',
    data: { limit: pageSize },
  }).then((res) => {
    // 将后端拼团列表映射为 TDesign activity 格式
    return (res?.list || []).map((item) => ({
      promotionId: String(item.activityId || item.spuId),
      title: item.title,
      description: null,
      promotionCode: 'GROUP_BUY',
      promotionSubCode: 'PT',
      tag: (item.tags && item.tags[0]?.title) || '拼团',
      timeType: 1,
      startTime: null,
      endTime: null,
      teasingStartTime: null,
      activityLadder: [{ label: item.title }],
      // 额外字段供页面使用
      thumb: item.thumb,
      price: item.price,
      originPrice: item.originPrice,
      spuId: item.spuId,
    }));
  });
}
