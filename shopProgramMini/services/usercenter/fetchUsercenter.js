import { config } from '../../config/index';
import { request } from '../request';

/** 获取个人中心信息 */
function mockFetchUserCenter() {
  const { delay } = require('../_utils/delay');
  const { genUsercenter } = require('../../model/usercenter');
  return delay(200).then(() => genUsercenter());
}

/** 获取个人中心信息 */
export function fetchUserCenter() {
  if (config.useMock) {
    return mockFetchUserCenter();
  }

  return request({
    url: '/api/v1/member/center',
    method: 'GET',
    needAuth: true,
  }).then((data = {}) => ({
    userInfo: data.userInfo || {},
    countsData: data.countsData || [],
    orderTagInfos: data.orderTagInfos || [],
    customerServiceInfo: data.customerServiceInfo || {},
  }));
}
