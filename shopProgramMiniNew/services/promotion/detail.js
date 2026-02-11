import { request } from '../request';
import { config } from '../../config/index';

/**
 * 获取秒杀促销列表（对应 TDesign promotion 页面）
 */
export function fetchPromotion(ID = 0) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getPromotion } = require('../../model/promotion');
    return delay().then(() => getPromotion(ID));
  }
  return request({ url: '/api/v1/seckill/products', method: 'GET', data: { limit: 20 } }).then(
    (res) => ({
      list: (res?.list || []).map((item) => ({
        spuId: item.spuId,
        thumb: item.thumb,
        title: item.title,
        price: item.price,
        originPrice: item.originPrice,
        tags: item.tags || [],
      })),
      banner: res?.banner || '',
      time: res?.time || 0,
      showBannerDesc: true,
      statusTag: res?.statusTag || 'expired',
    }),
  );
}
