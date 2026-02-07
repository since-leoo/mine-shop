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
  }).then((data = {}) => {
    const raw = data.user_info || {};
    return {
      userInfo: {
        avatarUrl: raw.avatar || '',
        nickName: raw.nickname || '',
        phoneNumber: raw.phone || '',
        gender: raw.gender || 'unknown',
        levelName: raw.level_name || null,
        authorizedProfile: Boolean(raw.authorized_profile),
        balance: raw.balance || 0,
        points: raw.points || 0,
      },
      countsData: data.counts_data || [],
      orderTagInfos: (data.order_tag_infos || []).map((item) => ({
        title: item.title,
        iconName: item.icon_name,
        orderNum: item.order_num || 0,
        tabType: item.tab_type,
        status: item.status,
      })),
      customerServiceInfo: {
        servicePhone: (data.customer_service_info || {}).service_phone || '',
        serviceTimeDuration: (data.customer_service_info || {}).service_time_duration || '',
      },
    };
  });
}
