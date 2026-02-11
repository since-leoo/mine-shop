import { request } from '../request';
import { config } from '../../config/index';

/**
 * 获取拼团活动详情
 * 目前后端只有按 activityId + spuId 查详情的接口
 * 小程序 activity 页面传的 ID 是 promotionId，暂时保持 mock
 */
export function fetchActivity(ID = 0) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getActivity } = require('../../model/activity');
    return delay().then(() => getActivity(ID));
  }
  // 拼团活动详情需要 activityId + spuId，单 ID 无法直接调用
  // 保持 mock 格式兼容
  const { getActivity } = require('../../model/activity');
  return Promise.resolve(getActivity(ID));
}
